<?php

namespace Database\Seeders;

use App\Models\Categorie;
use Illuminate\Database\Seeder;

class CategorieSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['nom' => 'Équipement sportif',                      'description' => 'Montres, capteurs, trackers et équipements connectés pour le sport'],
            ['nom' => 'Équipement informatique',                 'description' => 'Ordinateurs portables, tablettes, périphériques et accessoires IT'],
            ['nom' => 'Équipement médical',                      'description' => 'Moniteurs, appareils de mesure et dispositifs médicaux'],
            ['nom' => 'Équipement militaire',                    'description' => 'Terminaux durcis, radios et équipements de terrain'],
            ['nom' => 'Équipement de bureau',                    'description' => 'Imprimantes, téléphones IP, systèmes de visioconférence'],
            ['nom' => 'Équipements commerciaux / administratifs','description' => 'Terminaux de paiement, PDA commerciaux et outils terrain'],
        ];

        foreach ($categories as $data) {
            Categorie::firstOrCreate(['nom' => $data['nom']], $data);
        }
    }
}
