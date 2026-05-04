<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function verifyPhone(Request $request)
    {
        $userId = auth('sanctum')->id();
        $uniqueRule = $userId ? "unique:users,telephone,{$userId}" : "unique:users,telephone";

        $request->validate([
            'telephone' => [
                'required',
                'string',
                'regex:/^(\+228)(90|91|92|93|96|97|98|99|70|71|79)[0-9]{6}$/', // Vérification numéro togolais
                $uniqueRule,
            ],
        ], [
            'telephone.regex' => 'Le numéro doit être un numéro mobile togolais valide (ex: +22890000000).',
            'telephone.unique' => 'Ce numéro est déjà utilisé.',
        ]);

        return response()->json([
            'message' => 'Numéro valide et disponible.'
        ]);
    }

    public function verifyEmail(Request $request)
    {
        $userId = auth('sanctum')->id();
        $uniqueRule = $userId ? "unique:users,email,{$userId}" : "unique:users,email";

        $request->validate([
            'email' => [
                'required',
                'email',
                $uniqueRule,
            ],
        ], [
            'email.email' => 'Veuillez fournir une adresse email valide.',
            'email.unique' => 'Cet email est déjà utilisé.',
        ]);

        return response()->json([
            'message' => 'Email valide et disponible.'
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'telephone' => [
                'required',
                'string',
                'regex:/^(\+228)(90|91|92|93|96|97|98|99|70|71|79)[0-9]{6}$/',
                'unique:users,telephone',
            ],
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'telephone' => $request->telephone,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user->load(['categories', 'adresses']),
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'telephone' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('telephone', $request->telephone)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'telephone' => ['Les identifiants fournis sont incorrects.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user->load(['categories', 'adresses']),
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Déconnecté avec succès'
        ]);
    }

    public function socialLogin(Request $request)
    {
        $request->validate([
            'provider' => 'required|in:google,facebook,apple',
            'token' => 'required|string',
            'nom' => 'nullable|string',
            'email' => 'nullable|email',
            'telephone' => 'nullable|string|unique:users,telephone',
        ]);

        $provider = $request->provider;
        $token = $request->token;

        try {
            $providerId = $this->verifySocialToken($provider, $token);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Token invalide ou non autorisé'], 401);
        }

        $nom = $request->nom ?? 'Utilisateur ' . ucfirst($provider);

        // Recherche de l'utilisateur
        $user = User::where('provider_name', $provider)
                    ->where('provider_id', $providerId)
                    ->first();

        if (! $user) {
            // Création d'un nouvel utilisateur si non existant.
            // Note: Comme 'telephone' est unique et requis dans la DB, s'il n'est pas fourni par l'app,
            // on génère un faux numéro temporaire. Il est conseillé de demander le numéro côté front-end.
            $telephone = $request->telephone ?? 'tmp_' . $provider . '_' . time();

            $user = User::create([
                'nom' => $nom,
                'email' => $request->email,
                'telephone' => $telephone,
                'provider_name' => $provider,
                'provider_id' => $providerId,
                'password' => null,
            ]);
        } else {
            // Mettre à jour l'email s'il n'existait pas
            if (!$user->email && $request->email) {
                $user->update(['email' => $request->email]);
            }
        }

        $authToken = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user->load(['categories', 'adresses']),
            'token' => $authToken,
        ]);
    }

    private function verifySocialToken($provider, $token)
    {
        if ($provider === 'google') {
            $response = \Illuminate\Support\Facades\Http::get("https://oauth2.googleapis.com/tokeninfo", [
                'id_token' => $token
            ]);
            if ($response->successful() && isset($response['sub'])) {
                return (string) $response['sub'];
            }
        } elseif ($provider === 'facebook') {
            $response = \Illuminate\Support\Facades\Http::get("https://graph.facebook.com/me", [
                'access_token' => $token,
                'fields' => 'id'
            ]);
            if ($response->successful() && isset($response['id'])) {
                return (string) $response['id'];
            }
        } elseif ($provider === 'apple') {
            $parts = explode('.', $token);
            if (count($parts) === 3) {
                $payload = json_decode(base64_decode($parts[1]), true);
                if (isset($payload['sub'])) {
                    return (string) $payload['sub'];
                }
            }
        }

        throw new \Exception("Token invalide pour le fournisseur {$provider}");
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'nom' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'telephone' => [
                'nullable',
                'string',
                'regex:/^(\+228)(90|91|92|93|96|97|98|99|70|71|79)[0-9]{6}$/',
                'unique:users,telephone,' . $user->id,
            ],
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',
            'quartier_id' => 'nullable|exists:quartiers,id',
            'details' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $updateData = [];

        if ($request->filled('nom')) {
            $updateData['nom'] = $request->nom;
        }

        if ($request->has('email')) {
            $updateData['email'] = $request->email;
        }

        if ($request->filled('telephone')) {
            $updateData['telephone'] = $request->telephone;
        }

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('profiles', 'public');
            $updateData['avatar_url'] = \Illuminate\Support\Facades\Storage::url($path);
        }

        // Mise à jour des informations de base
        $user->update($updateData);

        // Synchronisation des préférences (catégories)
        if ($request->has('categories')) {
            $user->categories()->sync($request->categories);
        }

        // Création ou mise à jour de l'adresse
        if ($request->filled('quartier_id') || $request->filled('latitude')) {
            $adresse = $user->adresses()->first();
            if ($adresse) {
                $adresse->update([
                    'quartier_id' => $request->quartier_id ?? $adresse->quartier_id,
                    'details' => $request->details ?? $adresse->details,
                    'latitude' => $request->latitude ?? $adresse->latitude,
                    'longitude' => $request->longitude ?? $adresse->longitude,
                ]);
            } else {
                $user->adresses()->create([
                    'quartier_id' => $request->quartier_id,
                    'details' => $request->details,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                ]);
            }
        }

        return response()->json([
            'message' => 'Profil mis à jour avec succès',
            'user' => $user->load(['categories', 'adresses']),
        ]);
    }
}
