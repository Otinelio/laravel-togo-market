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
            ['nom' => 'Mode'],
            ['nom' => 'Beauté & Santé'],
            ['nom' => 'Électronique'],
            ['nom' => 'Alimentation'],
            ['nom' => 'Maison & Décoration'],
            ['nom' => 'Immobilier'],
            ['nom' => 'Véhicules'],
            ['nom' => 'Services'],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(
                ['slug' => Str::slug($cat['nom'])],
                [
                    'nom' => $cat['nom'],
                ]
            );
        }
    }
}
