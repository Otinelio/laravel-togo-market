<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Boutique;
use App\Models\Produit;
use App\Models\ImageProduit;
use App\Http\Requests\StoreProduitRequest;
use App\Http\Requests\UpdateProduitRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProduitController extends Controller
{
    /**
     * Liste publique des produits
     */
    public function index()
    {
        return response()->json(Produit::with(['boutique', 'categorie', 'images'])->latest()->paginate(20));
    }

    /**
     * Action du propriétaire : Ajouter un produit à sa boutique
     */
    public function store(StoreProduitRequest $request)
    {
        $boutique = $request->user()->boutique;

        if (!$boutique) {
            return response()->json(['message' => 'Vous ne possédez pas de boutique pour ajouter des produits.'], 403);
        }

        $validated = $request->validated();
        $validated['boutique_id'] = $boutique->id;

        if (!isset($validated['stock'])) {
            $validated['stock'] = 0;
        }

        $produit = Produit::create($validated);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $file) {
                $path = $file->store('produits', 'public');
                ImageProduit::create([
                    'produit_id' => $produit->id,
                    'chemin_image' => $path,
                    'is_principale' => $index === 0, // Première image comme principale
                ]);
            }
        }

        return response()->json([
            'message' => 'Produit ajouté avec succès',
            'produit' => $produit->load(['images', 'categorie'])
        ], 201);
    }

    /**
     * Route publique : Afficher un produit spécifique
     */
    public function show(Produit $produit)
    {
        return response()->json($produit->load(['boutique', 'categorie', 'images']));
    }

    /**
     * Action du propriétaire : Mettre à jour un produit
     */
    public function update(UpdateProduitRequest $request, Produit $produit)
    {
        if ($produit->boutique->user_id !== $request->user()->id) {
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
                    'produit_id' => $produit->id,
                    'chemin_image' => $path,
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
        if ($produit->boutique->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        // Si on fait du soft delete (par défaut sur le modèle Produit), 
        // on ne supprime pas forcément les images physiquement.
        // Si on souhaite supprimer les images physiquement, on décommente :
        // foreach ($produit->images as $image) {
        //     Storage::disk('public')->delete($image->chemin_image);
        // }
        // $produit->images()->delete();

        $produit->delete();

        return response()->json([
            'message' => 'Produit supprimé avec succès'
        ]);
    }
    
    /**
     * Route publique : Récupérer tous les produits d'une boutique
     */
    public function getByBoutique(Boutique $boutique)
    {
        return response()->json($boutique->produits()->with(['images', 'categorie'])->latest()->paginate(20));
    }
}
