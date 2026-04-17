<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class StoreBoutiqueRequest extends FormRequest
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
        
        if ($this->has('telephone')) {
            $mergeData['telephone'] = $this->normalizePhoneNumber($this->telephone);
        }
        
        if ($this->has('contacts') && is_array($this->contacts)) {
            $mergeData['contacts'] = array_map([$this, 'normalizePhoneNumber'], $this->contacts);
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
        
        // Remove spaces and any non-essential characters (optional: you can keep it just removing spaces)
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
        $phoneValidation = function ($attribute, $value, $fail) {
            $exists = DB::table('boutiques')
                ->where(function($q) use ($value) {
                    $q->where('telephone', $value)
                      ->orWhereJsonContains('contacts', $value);
                })
                ->exists();

            if ($exists) {
                if (str_starts_with($attribute, 'contacts.')) {
                    $fail('Ce contact secondaire est déjà enregistré par une autre boutique.');
                } else {
                    $fail('Ce numéro de téléphone est déjà pris par une autre boutique.');
                }
            }
        };

        return [
            'nom' => 'required|string|max:255|unique:boutiques,nom',
            'telephone' => [
                'required',
                'string',
                'max:255',
                $phoneValidation
            ],
            'adresse' => 'required|string|max:255',
            'details_adresse' => 'nullable|string',
            'contacts' => 'nullable|array',
            'contacts.*' => [
                'string',
                'different:telephone',
                $phoneValidation
            ],
            'description' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'banner' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'horaires' => 'nullable|array',
            'localisation' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'categories' => 'required|array',
            'categories.*' => 'exists:categories,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'contacts.*.different' => 'Le numéro secondaire ne peut pas être identique au numéro principal.',
        ];
    }
}
