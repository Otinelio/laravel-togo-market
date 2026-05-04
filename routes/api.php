<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\BoutiqueController;
use App\Http\Controllers\Api\ProduitController;
use App\Http\Controllers\Api\FavoriController;

// ── Routes publiques ───────────────────────────────────────────────────────────
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/locations', [LocationController::class, 'index']);

// IMPORTANT : les routes nommées doivent être avant {produit} pour ne pas être capturées
Route::get('/produits/trending/paginated', [ProduitController::class, 'trendingPaginated']);
Route::get('/produits/trending', [ProduitController::class, 'trendingHome']);
Route::get('/produits/zone', [ProduitController::class, 'byZone']);

Route::get('/produits', [ProduitController::class, 'index']);
Route::get('/produits/{produit}', [ProduitController::class, 'show']);
Route::get('/boutiques', [BoutiqueController::class, 'index']);
Route::get('/boutiques/{boutique}', [BoutiqueController::class, 'show']);
Route::get('/boutiques/{boutique}/produits', [ProduitController::class, 'getByBoutique']);
Route::get('/users/{user}', [\App\Http\Controllers\Api\UserController::class, 'show']);
Route::get('/users/{user}/produits', [ProduitController::class, 'getByUser']);

// ── Authentification ───────────────────────────────────────────────────────────
Route::post('/auth/verify-phone', [AuthController::class, 'verifyPhone']);
Route::post('/auth/verify-email', [AuthController::class, 'verifyEmail']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/social', [AuthController::class, 'socialLogin']);

// ── Routes protégées (auth:sanctum) ───────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);

    // Boutique du vendeur connecté
    Route::get('/boutique/me', [BoutiqueController::class, 'me']);
    Route::post('/boutique', [BoutiqueController::class, 'store']);
    Route::put('/boutique', [BoutiqueController::class, 'update']);
    Route::post('/boutique/validate-step', [BoutiqueController::class, 'validateStep']);

    // Gestion des produits (CRUD vendeur)
    Route::get('/mes-produits', [ProduitController::class, 'mesProduits']);
    Route::post('/produits', [ProduitController::class, 'store']);
    Route::put('/produits/{produit}', [ProduitController::class, 'update']);
    Route::delete('/produits/{produit}', [ProduitController::class, 'destroy']);

    // ── Favoris ──────────────────────────────────────────────────────────────
    Route::get('/favoris', [FavoriController::class, 'index']);
    Route::post('/produits/{produit}/toggle-favori', [FavoriController::class, 'toggle']);

    // Profil utilisateur
    Route::get('/user', function (Request $request) {
        return response()->json([
            'user' => $request->user()->load(['categories', 'adresses'])
        ]);
    });
});
