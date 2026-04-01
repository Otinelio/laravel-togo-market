<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        // On récupère uniquement les catégories parentes (principales)
        $categories = Category::whereNull('parent_id')->with('children')->get();
        return response()->json($categories);
    }
}
