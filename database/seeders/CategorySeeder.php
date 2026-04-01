<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['nom' => 'Mode', 'emoji' => '👗'],
            ['nom' => 'Beauté & Santé', 'emoji' => '💄'],
            ['nom' => 'Électronique', 'emoji' => '📱'],
            ['nom' => 'Alimentation', 'emoji' => '🍔'],
            ['nom' => 'Maison & Décoration', 'emoji' => '🏠'],
            ['nom' => 'Immobilier', 'emoji' => '🏢'],
            ['nom' => 'Véhicules', 'emoji' => '🚗'],
            ['nom' => 'Services', 'emoji' => '💼'],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(
                ['slug' => Str::slug($cat['nom'])],
                [
                    'nom' => $cat['nom'],
                    'emoji' => $cat['emoji'],
                ]
            );
        }
    }
}
