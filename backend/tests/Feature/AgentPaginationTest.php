<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_agents_index_returns_paginated_results(): void
    {
        $user = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => 'admin',
            'is_active' => true,
        ]);

        for ($i = 1; $i <= 15; $i++) {
            Agent::create([
                'matricule' => 'MAT-2026-' . str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'nom' => 'Agent' . $i,
                'prenom' => 'Prenom' . $i,
                'telephone' => '070000000' . $i,
                'email' => 'agent' . $i . '@example.com',
                'direction' => 'Direction',
                'service' => 'Service',
                'poste' => 'Poste',
                'statut' => 'actif',
            ]);
        }

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/agents?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'agents' => [['id', 'nom', 'prenom']],
                'total',
                'current_page',
                'last_page',
                'per_page',
            ])
            ->assertJsonPath('per_page', 10)
            ->assertJsonPath('current_page', 1)
            ->assertJsonCount(10, 'agents');
    }

    public function test_agents_index_can_filter_by_statut(): void
    {
        $user = User::create([
            'name' => 'Admin Test',
            'email' => 'admin2@example.com',
            'password' => 'password',
            'role' => 'admin',
            'is_active' => true,
        ]);

        Agent::create([
            'matricule' => 'MAT-2026-00001',
            'nom' => 'Actif',
            'prenom' => 'Agent',
            'email' => 'actif@example.com',
            'statut' => 'actif',
        ]);

        Agent::create([
            'matricule' => 'MAT-2026-00002',
            'nom' => 'Inactif',
            'prenom' => 'Agent',
            'email' => 'inactif@example.com',
            'statut' => 'inactif',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/agents?statut=actif&per_page=100');

        $response->assertOk()
            ->assertJsonCount(1, 'agents')
            ->assertJsonPath('agents.0.statut', 'actif');
    }
}
