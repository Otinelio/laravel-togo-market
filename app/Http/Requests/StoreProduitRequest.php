<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProduitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // On autorise la requête, la vérification de la boutique se fera dans le Controller
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'categorie_id' => 'required|exists:categories,id',
            'titre' => 'required|string|max:255',
            'description' => 'required|string',
            'prix' => 'required|numeric|min:0',
            'prix_negociable' => 'boolean',
            'etat' => 'required|in:Neuf,Occasion',
            'images' => 'required|array|min:1|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'localisation' => 'nullable|string',
            // fields from default, variation not highly prioritized here but keeping safe
            'variations_possibles' => 'nullable|array',
            'stock' => 'nullable|integer|min:0',
        ];
    }
}
