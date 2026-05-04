<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Boutique;
use App\Models\Produit;
use App\Models\ImageProduit;
use App\Http\Requests\StoreProduitRequest;
use App\Http\Requests\UpdateProduitRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class ProduitController extends Controller
{
    private const TRENDING_HOME_LIMIT = 10;

    private const TRENDING_PER_PAGE_DEFAULT = 20;

    private const TRENDING_PER_PAGE_MAX = 50;

    /**
     * Liste publique des produits (paginée).
     * Si l'utilisateur est authentifié, le champ is_favoris est inclus.
     */
    public function index()
    {
        $produits = Produit::with(['boutique', 'categorie', 'images', 'user'])
            ->where('statut', 'actif')
            ->latest()
            ->paginate(20);

        // Ajouter is_favoris pour chaque produit si user est connecté
        if (auth()->check()) {
            $userId = auth()->id();
            $produits->getCollection()->transform(function ($produit) use ($userId) {
                $produit->setAttribute('is_favoris', $produit->favoris()->where('user_id', $userId)->exists());
                return $produit;
            });
        }

        return response()->json($produits);
    }

    /**
     * Aperçu tendances (page d'accueil) : les 10 premiers produits actifs par score cumulé.
     */
    public function trendingHome()
    {
        $produits = Produit::trendingScore()
            ->with(['boutique', 'categorie', 'images', 'user'])
            ->limit(self::TRENDING_HOME_LIMIT)
            ->get();

        $this->attachTrendingFavorisFlag($produits);

        return response()->json($produits);
    }

    /**
     * Liste tendances complète, pagination serveur (même ordre que l'aperçu).
     */
    public function trendingPaginated(Request $request)
    {
        $perPage = (int) $request->query('per_page', self::TRENDING_PER_PAGE_DEFAULT);
        $perPage = max(1, min($perPage, self::TRENDING_PER_PAGE_MAX));

        $produits = Produit::trendingScore()
            ->with(['boutique', 'categorie', 'images', 'user'])
            ->paginate($perPage);

        $this->attachTrendingFavorisFlag($produits->getCollection());

        return response()->json($produits);
    }

    /**
     * @param  Collection<int, \App\Models\Produit>  $produits
     */
    private function attachTrendingFavorisFlag(Collection $produits): void
    {
        if (! auth()->check()) {
            return;
        }

        $userId = auth()->id();
        $produits->transform(function (Produit $produit) use ($userId) {
            $produit->setAttribute(
                'is_favoris',
                $produit->favoris()->where('user_id', $userId)->exists()
            );

            return $produit;
        });
    }

    /**
     * Route publique : Produits d'une zone géographique (filtrage texte)
     */
    public function byZone(Request $request)
    {
        $request->validate(['zone' => 'required|string|min:2']);
        $zone = $request->input('zone');

        $produits = Produit::byZone($zone)
            ->with(['boutique', 'categorie', 'images', 'user'])
            ->where('statut', 'actif')
            ->latest()
            ->paginate(20);

        return response()->json($produits);
    }

    /**
     * Action du propriétaire : Ajouter un produit à sa boutique
     */
    public function store(StoreProduitRequest $request)
    {
        $publishAs = $request->input('publish_as', 'boutique'); // 'boutique' or 'particulier'
        $validated = $request->validated();
        $validated['user_id'] = $request->user()->id;

        if ($publishAs === 'boutique') {
            $boutique = $request->user()->boutique;
            if (!$boutique) {
                return response()->json(['message' => 'Vous ne possédez pas de boutique pour ajouter des produits.'], 403);
            }
            $validated['boutique_id'] = $boutique->id;
        } else {
            $validated['boutique_id'] = null;
        }

        if (!isset($validated['stock'])) {
            $validated['stock'] = 0;
        }

        $produit = Produit::create($validated);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $file) {
                $path = $file->store('produits', 'public');
                ImageProduit::create([
                    'produit_id'    => $produit->id,
                    'chemin_image'  => $path,
                    'is_principale' => $index === 0,
                ]);
            }
        }

        return response()->json([
            'message' => 'Produit ajouté avec succès',
            'produit' => $produit->load(['images', 'categorie'])
        ], 201);
    }

    /**
     * Route publique : Afficher un produit spécifique + incrémenter vues
     */
    public function show(Produit $produit)
    {
        // Incrémenter le compteur de vues (atomic pour éviter race conditions)
        $produit->increment('vues');

        $produit->load(['boutique', 'categorie', 'images', 'user']);

        // Ajouter is_favoris si connecté
        if (auth()->check()) {
            $produit->setAttribute('is_favoris', $produit->favoris()->where('user_id', auth()->id())->exists());
        }

        return response()->json($produit);
    }

    /**
     * Action du propriétaire : Mettre à jour un produit
     */
    public function update(UpdateProduitRequest $request, Produit $produit)
    {
        if ($produit->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé. Ce produit ne vous appartient pas.'], 403);
        }

        $validated = $request->validated();
        $produit->update($validated);

        // Supprimer les images qui ne sont pas dans 'images_a_garder'
        $imagesAGarder = $request->input('images_a_garder', []);
        $imagesASupprimer = $produit->images()->whereNotIn('id', $imagesAGarder)->get();

        foreach ($imagesASupprimer as $image) {
            if (Storage::disk('public')->exists($image->chemin_image)) {
                Storage::disk('public')->delete($image->chemin_image);
            }
            $image->delete();
        }

        // Ajouter de nouvelles images
        if ($request->hasFile('nouvelles_images')) {
            foreach ($request->file('nouvelles_images') as $file) {
                $path = $file->store('produits', 'public');
                ImageProduit::create([
                    'produit_id'    => $produit->id,
                    'chemin_image'  => $path,
                    'is_principale' => false,
                ]);
            }
        }

        // S'assurer qu'il y a toujours une image principale
        $mainImage = $produit->images()->where('is_principale', true)->first();
        if (!$mainImage) {
            $firstImage = $produit->images()->first();
            if ($firstImage) {
                $firstImage->update(['is_principale' => true]);
            }
        }

        return response()->json([
            'message' => 'Produit mis à jour avec succès',
            'produit' => $produit->load(['images', 'categorie'])
        ]);
    }

    /**
     * Action du propriétaire : Supprimer un produit
     */
    public function destroy(Request $request, Produit $produit)
    {
        if ($produit->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $produit->delete();

        return response()->json(['message' => 'Produit supprimé avec succès']);
    }

    /**
     * Route publique : Récupérer tous les produits d'une boutique
     */
    public function getByBoutique(Boutique $boutique)
    {
        return response()->json(
            $boutique->produits()->with(['images', 'categorie', 'user'])->latest()->paginate(20)
        );
    }

    /**
     * Route protégée : Récupérer les produits personnels de l'utilisateur connecté
     */
    public function mesProduits(Request $request)
    {
        return response()->json(
            $request->user()->annonces_personnelles()->with(['images', 'categorie', 'user'])->latest()->paginate(20)
        );
    }

    /**
     * Route publique : Récupérer les produits personnels d'un utilisateur spécifique
     */
    public function getByUser(\App\Models\User $user)
    {
        return response()->json(
            $user->annonces_personnelles()->with(['images', 'categorie', 'user'])->latest()->paginate(20)
        );
    }
}
