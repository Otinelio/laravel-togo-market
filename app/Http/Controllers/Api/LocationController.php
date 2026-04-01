<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Ville;

class LocationController extends Controller
{
    public function index()
    {
        $villes = Ville::with('quartiers')->get();
        return response()->json($villes);
    }
}
