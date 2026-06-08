<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Comptes système (admin & gestionnaire) ────────────────
        User::create([
            'name'      => 'Alexandre Martin',
            'email'     => 'admin@equip.com',
            'password'  => Hash::make('password'),
            'role'      => 'admin',
            'is_active' => true,
        ]);

        User::create([
            'name'      => 'Gestionnaire Parc',
            'email'     => 'gestion@equip.com',
            'password'  => Hash::make('password'),
            'role'      => 'gestionnaire',
            'is_active' => true,
        ]);

        // ── Seeders dans l'ordre des dépendances ──────────────────
        $this->call([
            CategorieSeeder::class,   // 1. Catégories
            AgentSeeder::class,       // 2. Agents + leurs comptes users (rôle agent)
            EquipementSeeder::class,  // 3. Équipements (dépend des catégories)
        ]);
    }
}
