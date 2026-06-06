<?php

namespace Database\Seeders;

use App\Models\User;
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
            'email' => 'admin@admin.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Gestionnaire Parc',
            'email' => 'gestion@admin.com',
            'password' => Hash::make('password'),
            'role' => 'gestionnaire',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Agent Test',
            'email' => 'agent@admin.com',
            'password' => Hash::make('password'),
            'role' => 'agent',
            'is_active' => true,
        ]);

        // Appel des autres seeders
        $this->call([
            CategorieSeeder::class,
        ]);
    }
}
