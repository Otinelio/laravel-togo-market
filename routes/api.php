<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\LocationController;

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/locations', [LocationController::class, 'index']);

Route::post('/auth/verify-phone', [AuthController::class, 'verifyPhone']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/social', [AuthController::class, 'socialLogin']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
