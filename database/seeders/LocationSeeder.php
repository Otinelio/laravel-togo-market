<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Quartier;
use App\Models\Ville;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $villes = [
            'Lomé' => ['Adidogomé', 'Agoè', 'Bè', 'Tokoin', 'Amoutiévé', 'Hédzranawoé'],
            'Avepozo' => ['Avepozo Centre', 'Plage', 'Kpogan'],
            'Baguida' => ['Baguida Centre', 'Doèvi Kopé', 'Monument'],
        ];

        foreach ($villes as $villeNom => $quartiers) {
            $ville = Ville::firstOrCreate(['nom' => $villeNom]);
            foreach ($quartiers as $quartierNom) {
                Quartier::firstOrCreate([
                    'ville_id' => $ville->id,
                    'nom' => $quartierNom,
                ]);
            }
        }
    }
}
