<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Categorie;
use App\Models\Equipement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Création des utilisateurs par défaut
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@equip.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Gestionnaire Parc',
            'email' => 'gestion@equip.com',
            'password' => Hash::make('password'),
            'role' => 'gestionnaire',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Agent Test',
            'email' => 'agent@equip.com',
            'password' => Hash::make('password'),
            'role' => 'agent',
            'is_active' => true,
        ]);

        // Création de 5 catégories avec exactement 5 équipements chacune
        Categorie::factory()
            ->count(5)
            ->create()
            ->each(function ($categorie) {
                Equipement::factory()
                    ->count(5) // Exactement 5 équipements par catégorie
                    ->create([
                        'categorie_id' => $categorie->id
                    ]);
            });

        // On n'appelle plus CategorieSeeder car on utilise les factories pour plus de flexibilité
        // $this->call([
        //     CategorieSeeder::class,
        // ]);
    }
}
