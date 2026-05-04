<?php

namespace Tests\Feature;

use App\Models\Boutique;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProduitBoutiqueCategoryValidationTest extends TestCase
{
    use RefreshDatabase;

    private function makeSellerWithBoutiqueAndCategories(): array
    {
        $user = User::factory()->create(['telephone' => '+22890123001']);

        $boutique = Boutique::create([
            'user_id' => $user->id,
            'nom' => 'Boutique test '.uniqid(),
            'slug' => 'boutique-'.uniqid(),
            'telephone' => '+228901239'.substr((string) random_int(10000, 99999), 0, 5),
            'description' => 'Test',
        ]);

        $allowedParent = Category::create([
            'nom' => 'Parent autorisé',
            'slug' => 'parent-ok-'.uniqid(),
            'parent_id' => null,
        ]);

        $allowedChild = Category::create([
            'nom' => 'Enfant',
            'slug' => 'child-ok-'.uniqid(),
            'parent_id' => $allowedParent->id,
        ]);

        $otherRoot = Category::create([
            'nom' => 'Autre racine',
            'slug' => 'other-root-'.uniqid(),
            'parent_id' => null,
        ]);

        $boutique->categories()->sync([$allowedParent->id]);

        return [$user, $boutique, $allowedParent, $allowedChild, $otherRoot];
    }

    public function test_store_rejects_category_outside_boutique_tree(): void
    {
        [$user, , , , $otherRoot] = $this->makeSellerWithBoutiqueAndCategories();

        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

        $response = $this->post('/api/produits', [
            'categorie_id' => $otherRoot->id,
            'titre' => 'Article',
            'description' => 'Description',
            'prix' => 1500,
            'etat' => 'Neuf',
            'images' => [$file],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['categorie_id']);
    }

    public function test_store_accepts_descendant_of_boutique_category(): void
    {
        [$user, , , $allowedChild] = $this->makeSellerWithBoutiqueAndCategories();

        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

        $response = $this->post('/api/produits', [
            'categorie_id' => $allowedChild->id,
            'titre' => 'Article enfant',
            'description' => 'Description',
            'prix' => 2000,
            'etat' => 'Neuf',
            'images' => [$file],
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('produits', [
            'categorie_id' => $allowedChild->id,
            'titre' => 'Article enfant',
        ]);
    }
}
