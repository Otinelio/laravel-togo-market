<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display the specified user profile.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(User $user)
    {
        // Load relationships if needed, e.g., adresses
        $user->load(['adresses']);

        return response()->json([
            'status' => 'success',
            'data' => $user->only(['id', 'nom', 'prenom', 'email', 'telephone', 'avatar_url', 'created_at', 'adresses']),
        ]);
    }
}
