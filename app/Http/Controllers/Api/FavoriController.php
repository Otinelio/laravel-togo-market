<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favori;
use App\Models\Produit;
use Illuminate\Http\Request;

class FavoriController extends Controller
{
    /**
     * Liste les produits mis en favoris par l'utilisateur authentifié.
     */
    public function index(Request $request)
    {
        $favoris = Favori::with(['produit.images', 'produit.categorie', 'produit.boutique'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(fn($f) => $f->produit)
            ->filter();

        return response()->json($favoris->values());
    }

    /**
     * Toggle favori : ajoute si absent, retire si présent.
     * Retourne le nouveau statut.
     */
    public function toggle(Request $request, Produit $produit)
    {
        $userId = $request->user()->id;

        $existing = Favori::where('user_id', $userId)
            ->where('produit_id', $produit->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $isFavoris = false;
        } else {
            Favori::create([
                'user_id'    => $userId,
                'produit_id' => $produit->id,
            ]);
            $isFavoris = true;
        }

        return response()->json([
            'is_favoris' => $isFavoris,
            'produit_id' => $produit->id,
        ]);
    }
}
