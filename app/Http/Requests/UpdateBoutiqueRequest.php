<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class UpdateBoutiqueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $mergeData = [];
        
        if ($this->has('telephone') && !empty($this->telephone)) {
            $mergeData['telephone'] = $this->normalizePhoneNumber($this->telephone);
        }
        
        if ($this->has('contacts') && is_array($this->contacts)) {
            $normalizedContacts = array_values(
                array_filter(
                    array_map([$this, 'normalizePhoneNumber'], $this->contacts),
                    fn($v) => !empty($v)
                )
            );
            $mergeData['contacts'] = $normalizedContacts;
        }
        
        if (!empty($mergeData)) {
            $this->merge($mergeData);
        }
    }

    /**
     * Normalize a phone number by removing spaces and adding prefix prefix.
     *
     * @param string|null $number
     * @return string|null
     */
    protected function normalizePhoneNumber($number)
    {
        if (empty($number)) {
            return $number;
        }
        
        // Remove spaces
        $number = preg_replace('/\s+/', '', $number);
        
        // Prefix with +228 if it doesn't start with '+'
        if (!str_starts_with($number, '+')) {
            $number = '+228' . ltrim($number, '0');
        }
        
        return $number;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $boutiqueId = $this->user()->boutique ? $this->user()->boutique->id : null;

        $phoneValidation = function ($attribute, $value, $fail) use ($boutiqueId) {
            $exists = DB::table('boutiques')
                ->where(function($q) use ($value) {
                    $q->where('telephone', $value)
                      ->orWhereJsonContains('contacts', $value);
                })
                ->when($boutiqueId, function($q) use ($boutiqueId) {
                    return $q->where('id', '!=', $boutiqueId);
                })->exists();

            if ($exists) {
                if (str_starts_with($attribute, 'contacts.')) {
                    $fail('Ce contact secondaire est déjà enregistré par une autre boutique.');
                } else {
                    $fail('Ce numéro de téléphone est déjà pris par une autre boutique.');
                }
            }
        };

        return [
            'nom' => 'sometimes|required|string|max:255|unique:boutiques,nom,' . $boutiqueId,
            'telephone' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                $phoneValidation
            ],
            'adresse' => 'sometimes|required|string|max:255',
            'details_adresse' => 'nullable|string',
            'contacts' => 'nullable|array',
            'contacts.*' => [
                'string',
                $phoneValidation
            ],
            'description' => 'nullable|string',
            'logo_url' => 'nullable|string',
            'horaires' => 'nullable|array',
            'categorie_id' => 'nullable|exists:categories,id',
            'localisation' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ];
    }
}
