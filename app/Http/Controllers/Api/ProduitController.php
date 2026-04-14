<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Boutique;
use App\Models\Produit;
use Illuminate\Http\Request;

class ProduitController extends Controller
{
    /**
     * Liste publique des produits (Option recommandée pour exposer publiquement)
     */
    public function index()
    {
        return response()->json(Produit::with(['boutique', 'categorie'])->latest()->paginate(20));
    }

    /**
     * Action du propriétaire : Ajouter un produit à sa boutique
     */
    public function store(Request $request)
    {
        $boutique = $request->user()->boutique;

        if (!$boutique) {
            return response()->json(['message' => 'Vous ne possédez pas de boutique pour ajouter des produits.'], 403);
        }

        $validated = $request->validate([
            'categorie_id' => 'required|exists:categories,id',
            'titre' => 'required|string|max:255',
            'description' => 'required|string',
            'prix' => 'required|numeric|min:0',
            'etat' => 'required|in:Neuf,Occasion',
            'localisation' => 'nullable|string',
            'variations_possibles' => 'nullable|array',
            'stock' => 'integer|min:0',
            'statut' => 'in:actif,reserve,vendu'
        ]);

        $validated['boutique_id'] = $boutique->id;

        if (!isset($validated['stock'])) {
            $validated['stock'] = 0;
        }

        $produit = Produit::create($validated);

        return response()->json([
            'message' => 'Produit ajouté avec succès',
            'produit' => $produit
        ], 201);
    }

    /**
     * Route publique : Afficher un produit spécifique
     */
    public function show(Produit $produit)
    {
        return response()->json($produit->load(['boutique', 'categorie']));
    }

    /**
     * Action du propriétaire : Mettre à jour un produit
     */
    public function update(Request $request, Produit $produit)
    {
        // Sécurité : vérifier que le produit appartient bien au propriétaire authentifié
        if ($produit->boutique->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé. Ce produit ne vous appartient pas.'], 403);
        }

        $validated = $request->validate([
            'categorie_id' => 'sometimes|required|exists:categories,id',
            'titre' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'prix' => 'sometimes|required|numeric|min:0',
            'etat' => 'sometimes|required|in:Neuf,Occasion',
            'localisation' => 'nullable|string',
            'variations_possibles' => 'nullable|array',
            'stock' => 'sometimes|required|integer|min:0',
            'statut' => 'sometimes|required|in:actif,reserve,vendu'
        ]);

        $produit->update($validated);

        return response()->json([
            'message' => 'Produit mis à jour avec succès',
            'produit' => $produit
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

        $produit->delete();

        return response()->json([
            'message' => 'Produit supprimé avec succès'
        ]);
    }
    
    /**
     * Route publique : Récupérer tous les produits d'une boutique spécifique
     */
    public function getByBoutique(Boutique $boutique)
    {
        return response()->json($boutique->produits()->latest()->paginate(20));
    }
}
