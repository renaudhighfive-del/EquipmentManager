<?php

namespace Database\Seeders;

use App\Models\Categorie;
use Illuminate\Database\Seeder;

class CategorieSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['nom' => 'PDA', 'description' => 'Assistant numérique personnel pour scan et inventaire'],
            ['nom' => 'Smartphone', 'description' => 'Téléphones mobiles professionnels'],
            ['nom' => 'Tablette', 'description' => 'Tablettes tactiles'],
            ['nom' => 'Scanner', 'description' => 'Lecteurs de codes-barres autonomes'],
            ['nom' => 'Ordinateur portable', 'description' => 'Laptops et stations de travail mobiles'],
            ['nom' => 'Accessoire', 'description' => 'Chargeurs, batteries, housses, etc.'],
        ];

        foreach ($categories as $categorie) {
            Categorie::create($categorie);
        }
    }
}
