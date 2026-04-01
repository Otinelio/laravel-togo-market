<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_phone()
    {
        $response = $this->postJson('/api/auth/verify-phone', [
            'telephone' => '+22890000000',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Numéro valide et disponible.']);
    }

    public function test_user_can_register()
    {
        $response = $this->postJson('/api/auth/register', [
            'telephone' => '+22890000000',
            'password' => 'password123',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['user', 'token']);
                 
        $this->assertDatabaseHas('users', [
            'telephone' => '+22890000000',
            'nom' => null,
        ]);
    }

    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'nom' => 'Login User',
            'email' => 'test@example.com',
            'telephone' => '+22891111111',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'telephone' => '+22891111111',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['user', 'token']);
    }

    public function test_social_login_creates_new_user()
    {
        \Illuminate\Support\Facades\Http::fake([
            'oauth2.googleapis.com/tokeninfo*' => \Illuminate\Support\Facades\Http::response(['sub' => '1234567890'], 200),
        ]);

        $response = $this->postJson('/api/auth/social', [
            'provider' => 'google',
            'token' => 'fake_google_token_123',
            'nom' => 'Google User',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['user', 'token']);
                 
        $this->assertDatabaseHas('users', [
            'provider_name' => 'google',
            'provider_id' => '1234567890',
        ]);
    }

    public function test_social_login_fails_with_invalid_token()
    {
        \Illuminate\Support\Facades\Http::fake([
            'oauth2.googleapis.com/tokeninfo*' => \Illuminate\Support\Facades\Http::response(['error' => 'invalid_token'], 400),
        ]);

        $response = $this->postJson('/api/auth/social', [
            'provider' => 'google',
            'token' => 'invalid_token',
        ]);

        $response->assertStatus(401);
    }

    public function test_user_can_update_profile()
    {
        $user = User::factory()->create([
            'nom' => null,
            'telephone' => '+22892222222',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->actingAs($user)->putJson('/api/user/profile', [
            'nom' => 'Nouveau Nom',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'nom' => 'Nouveau Nom',
        ]);
    }

    public function test_verify_phone_rejects_invalid_prefixes()
    {
        $response = $this->postJson('/api/auth/verify-phone', [
            'telephone' => '+22880000000', // 80 n'est pas un préfixe valide
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['telephone']);
    }
}
