<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\BoutiqueController;
use App\Http\Controllers\Api\ProduitController;

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/locations', [LocationController::class, 'index']);

Route::get('/produits', [ProduitController::class, 'index']);
Route::get('/produits/{produit}', [ProduitController::class, 'show']);
Route::get('/boutiques/{boutique}', [BoutiqueController::class, 'show']);
Route::get('/boutiques/{boutique}/produits', [ProduitController::class, 'getByBoutique']);

Route::post('/auth/verify-phone', [AuthController::class, 'verifyPhone']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/social', [AuthController::class, 'socialLogin']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    
    // Admin Boutique
    Route::get('/boutique/me', [BoutiqueController::class, 'me']);
    Route::post('/boutique', [BoutiqueController::class, 'store']);
    Route::put('/boutique', [BoutiqueController::class, 'update']);
    
    // Admin Produits
    Route::post('/produits', [ProduitController::class, 'store']);
    Route::put('/produits/{produit}', [ProduitController::class, 'update']);
    Route::delete('/produits/{produit}', [ProduitController::class, 'destroy']);
    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
