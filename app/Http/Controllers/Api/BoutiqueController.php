<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Boutique;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Requests\StoreBoutiqueRequest;
use App\Http\Requests\UpdateBoutiqueRequest;

class BoutiqueController extends Controller
{
    /**
     * Liste des boutiques (pour la page d'accueil)
     */
    public function index()
    {
        // Retourne les boutiques avec leurs catégories (et éventuellement le nombre de produits ou la note moyenne)
        $boutiques = \App\Models\Boutique::with('categories')
            ->withCount('produits')
            ->orderBy('note_moyenne', 'desc')
            ->get();
            
        return response()->json($boutiques);
    }
    /**
     * Obtenir la boutique de l'utilisateur connecté
     * Utile pour vérifier si l'utilisateur doit voir le flow de configuration ou son Dashboard.
     */
    public function me(Request $request)
    {
        $boutique = $request->user()->boutique;
        
        if (!$boutique) {
            return response()->json(['message' => 'Aucune boutique trouvée pour cet utilisateur.'], 404);
        }

        return response()->json($boutique->load('categories'));
    }

    /**
     * Configuration initiale : Créer une boutique pour l'utilisateur
     */
    public function store(StoreBoutiqueRequest $request)
    {
        $user = $request->user();

        if ($user->boutique) {
            return response()->json(['message' => 'Vous possédez déjà une boutique.'], 403);
        }

        $validated = $request->validated();

        $validated['slug'] = Str::slug($validated['nom']) . '-' . uniqid();
        $validated['user_id'] = $user->id;

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('boutiques', 'public');
            $validated['logo_url'] = \Illuminate\Support\Facades\Storage::url($path);
        }

        if ($request->hasFile('banner')) {
            $path = $request->file('banner')->store('boutiques', 'public');
            $validated['banner_url'] = \Illuminate\Support\Facades\Storage::url($path);
        }

        $boutique = Boutique::create($validated);
        $boutique->categories()->attach($request->categories);

        return response()->json([
            'message' => 'Boutique créée avec succès',
            'boutique' => $boutique->load('categories')
        ], 201);
    }

    /**
     * Mettre à jour les informations de la boutique enregistrée
     */
    public function update(UpdateBoutiqueRequest $request)
    {
        $boutique = $request->user()->boutique;

        if (!$boutique) {
            return response()->json(['message' => 'Boutique introuvable.'], 404);
        }

        $validated = $request->validated();

        if (isset($validated['nom'])) {
            $validated['slug'] = Str::slug($validated['nom']) . '-' . uniqid();
        }

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('boutiques', 'public');
            $validated['logo_url'] = \Illuminate\Support\Facades\Storage::url($path);
        }

        if ($request->hasFile('banner')) {
            $path = $request->file('banner')->store('boutiques', 'public');
            $validated['banner_url'] = \Illuminate\Support\Facades\Storage::url($path);
        }

        $boutique->update($validated);

        if ($request->has('categories')) {
            $boutique->categories()->sync($request->categories);
        }

        return response()->json([
            'message' => 'Boutique mise à jour avec succès',
            'boutique' => $boutique->load('categories')
        ]);
    }
    
    /**
     * Route publique : Afficher les détails d'une boutique
     */
    public function show(Boutique $boutique)
    {
        return response()->json($boutique->load(['produits', 'categories']));
    }

    /**
     * Validation step by step for frontend creation
     */
    public function validateStep(Request $request)
    {
        $step = $request->input('step');
        $rules = [];
        $messages = [];

        if ($step == 1) {
            $rules = [
                'nom' => 'required|string|max:255|unique:boutiques,nom',
            ];
            $messages = [
                'nom.unique' => 'Ce nom de boutique est déjà pris.',
            ];
        } elseif ($step == 3) {
            $phoneValidation = function ($attribute, $value, $fail) use ($request) {
                if (!preg_match('/^\+228(90|91|92|93|96|97|98|99|70|71|79)\d{6}$/', $value)) {
                    $fail("Le numéro $value n'est pas un numéro togolais valide.");
                    return;
                }

                $isMain = $attribute === 'telephone';
                $exists = \Illuminate\Support\Facades\DB::table('boutiques')
                    ->where(function($q) use ($value) {
                        $q->where('telephone', $value)
                          ->orWhereJsonContains('contacts', $value);
                    });
                
                if ($request->user() && $request->user()->boutique) {
                    $exists->where('id', '!=', $request->user()->boutique->id);
                }

                if ($exists->exists()) {
                    $fail("Le numéro $value est déjà utilisé par une autre boutique.");
                }
            };

            $rules = [
                'telephone' => ['required', 'string', 'max:255', $phoneValidation],
                'contacts' => ['nullable', 'array'],
                'contacts.*' => ['string', 'different:telephone', $phoneValidation]
            ];
            
            $data = $request->all();
            if (isset($data['telephone'])) {
                $data['telephone'] = $this->normalizePhoneNumber($data['telephone']);
            }
            if (isset($data['contacts']) && is_array($data['contacts'])) {
                $data['contacts'] = array_map([$this, 'normalizePhoneNumber'], $data['contacts']);
            }
            $request->replace($data);
            
            $messages = [
                'contacts.*.different' => 'Le numéro secondaire ne peut pas être identique au numéro principal.',
            ];
        }

        if (!empty($rules)) {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), $rules, $messages);
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
        }

        return response()->json(['message' => 'Validation OK']);
    }

    protected function normalizePhoneNumber($number)
    {
        if (empty($number)) {
            return $number;
        }
        $number = preg_replace('/\s+/', '', $number);
        if (!str_starts_with($number, '+')) {
            $number = '+228' . ltrim($number, '0');
        }
        return $number;
    }
}
