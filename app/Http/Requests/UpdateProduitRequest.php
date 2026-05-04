<?php

namespace App\Http\Requests;

use App\Support\ProductCategoryAllowedForBoutique;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProduitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
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
            'categorie_id' => [
                'sometimes',
                'exists:categories,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null || $value === '') {
                        return;
                    }
                    $produit = $this->route('produit');
                    if ($produit && $produit->is_personnel) {
                        return; // Les particuliers peuvent choisir toutes les catégories
                    }
                    $boutique = $this->user()?->boutique;
                    if (! \App\Support\ProductCategoryAllowedForBoutique::check($boutique, (int) $value)) {
                        $fail('La catégorie choisie n’est pas parmi celles autorisées pour votre boutique.');
                    }
                },
            ],
            'titre' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'prix' => 'sometimes|numeric|min:0',
            'prix_negociable' => 'boolean',
            'etat' => 'sometimes|in:Neuf,Occasion',
            
            // Pour editer, on peut fournir de nouvelles images et retirer des anciennes 
            // pour ne pas compliquer : nouvelles_images + images_existantes_a_garder (ids)
            'nouvelles_images' => 'nullable|array|max:5',
            'nouvelles_images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'images_a_garder' => 'nullable|array',
            'images_a_garder.*' => 'exists:images_produit,id',
            
            'localisation' => 'nullable|string',
            'variations_possibles' => 'nullable|array',
            'stock' => 'nullable|integer|min:0',
            'statut' => 'sometimes|in:actif,reserve,vendu'
        ];
    }
}
