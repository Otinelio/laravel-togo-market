<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Boutique;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Requests\StoreBoutiqueRequest;
use App\Http\Requests\UpdateBoutiqueRequest;

class BoutiqueController extends Controller
{
    /**
     * Obtenir la boutique de l'utilisateur connecté
     * Utile pour vérifier si l'utilisateur doit voir le flow de configuration ou son Dashboard.
     */
    public function me(Request $request)
    {
        $boutique = $request->user()->boutique;
        
        if (!$boutique) {
            return response()->json(['message' => 'Aucune boutique trouvée pour cet utilisateur.'], 404);
        }

        return response()->json($boutique->load('categories'));
    }

    /**
     * Configuration initiale : Créer une boutique pour l'utilisateur
     */
    public function store(StoreBoutiqueRequest $request)
    {
        $user = $request->user();

        if ($user->boutique) {
            return response()->json(['message' => 'Vous possédez déjà une boutique.'], 403);
        }

        $validated = $request->validated();

        $validated['slug'] = Str::slug($validated['nom']) . '-' . uniqid();
        $validated['user_id'] = $user->id;

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('boutiques', 'public');
            $validated['logo_url'] = \Illuminate\Support\Facades\Storage::url($path);
        }

        if ($request->hasFile('banner')) {
            $path = $request->file('banner')->store('boutiques', 'public');
            $validated['banner_url'] = \Illuminate\Support\Facades\Storage::url($path);
        }

        $boutique = Boutique::create($validated);
        $boutique->categories()->attach($request->categories);

        return response()->json([
            'message' => 'Boutique créée avec succès',
            'boutique' => $boutique->load('categories')
        ], 201);
    }

    /**
     * Mettre à jour les informations de la boutique enregistrée
     */
    public function update(UpdateBoutiqueRequest $request)
    {
        $boutique = $request->user()->boutique;

        if (!$boutique) {
            return response()->json(['message' => 'Boutique introuvable.'], 404);
        }

        $validated = $request->validated();

        if (isset($validated['nom'])) {
            $validated['slug'] = Str::slug($validated['nom']) . '-' . uniqid();
        }

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('boutiques', 'public');
            $validated['logo_url'] = \Illuminate\Support\Facades\Storage::url($path);
        }

        if ($request->hasFile('banner')) {
            $path = $request->file('banner')->store('boutiques', 'public');
            $validated['banner_url'] = \Illuminate\Support\Facades\Storage::url($path);
        }

        $boutique->update($validated);

        if ($request->has('categories')) {
            $boutique->categories()->sync($request->categories);
        }

        return response()->json([
            'message' => 'Boutique mise à jour avec succès',
            'boutique' => $boutique->load('categories')
        ]);
    }
    
    /**
     * Route publique : Afficher les détails d'une boutique
     */
    public function show(Boutique $boutique)
    {
        return response()->json($boutique->load(['produits', 'categories']));
    }
}
