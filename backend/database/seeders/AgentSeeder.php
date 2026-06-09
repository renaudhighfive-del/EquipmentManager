<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AgentSeeder extends Seeder
{
    public function run(): void
    {
        $agents = [
            [
                'matricule'  => 'MAT-2024001',
                'nom'        => 'Dubois',
                'prenom'     => 'Amélie',
                'telephone'  => '+33 6 30 81 61 35',
                'email'      => 'a.dubois@entreprise.fr',
                'direction'  => 'Logistique',
                'service'    => 'Entrepôt A',
                'poste'      => 'Agent',
                'statut'     => 'actif',
            ],
            [
                'matricule'  => 'MAT-2024002',
                'nom'        => 'Martin',
                'prenom'     => 'Thomas',
                'telephone'  => '+33 6 12 34 56 78',
                'email'      => 't.martin@entreprise.fr',
                'direction'  => 'Informatique',
                'service'    => 'Support',
                'poste'      => 'Technicien',
                'statut'     => 'actif',
            ],
            [
                'matricule'  => 'MAT-2024003',
                'nom'        => 'Bernard',
                'prenom'     => 'Sophie',
                'telephone'  => '+33 6 98 76 54 32',
                'email'      => 's.bernard@entreprise.fr',
                'direction'  => 'RH',
                'service'    => 'Administration',
                'poste'      => 'Responsable RH',
                'statut'     => 'actif',
            ],
            [
                'matricule'  => 'MAT-2024004',
                'nom'        => 'Leroy',
                'prenom'     => 'Nicolas',
                'telephone'  => '+33 6 55 44 33 22',
                'email'      => 'n.leroy@entreprise.fr',
                'direction'  => 'Logistique',
                'service'    => 'Entrepôt B',
                'poste'      => 'Chef d\'équipe',
                'statut'     => 'actif',
            ],
            [
                'matricule'  => 'MAT-2024005',
                'nom'        => 'Moreau',
                'prenom'     => 'Julie',
                'telephone'  => '+33 6 11 22 33 44',
                'email'      => 'j.moreau@entreprise.fr',
                'direction'  => 'Commercial',
                'service'    => 'Ventes',
                'poste'      => 'Commerciale terrain',
                'statut'     => 'actif',
            ],
            [
                'matricule'  => 'MAT-2024006',
                'nom'        => 'Petit',
                'prenom'     => 'Karim',
                'telephone'  => '+33 6 77 88 99 00',
                'email'      => 'k.petit@entreprise.fr',
                'direction'  => 'Logistique',
                'service'    => 'Entrepôt A',
                'poste'      => 'Agent',
                'statut'     => 'inactif',
            ],
            [
                'matricule'  => 'MAT-2024007',
                'nom'        => 'Roux',
                'prenom'     => 'Isabelle',
                'telephone'  => '+33 6 33 44 55 66',
                'email'      => 'i.roux@entreprise.fr',
                'direction'  => 'Finance',
                'service'    => 'Comptabilité',
                'poste'      => 'Comptable',
                'statut'     => 'actif',
            ],
            [
                'matricule'  => 'MAT-2024008',
                'nom'        => 'Simon',
                'prenom'     => 'Luc',
                'telephone'  => '+33 6 22 11 44 55',
                'email'      => 'l.simon@entreprise.fr',
                'direction'  => 'Informatique',
                'service'    => 'Développement',
                'poste'      => 'Développeur',
                'statut'     => 'actif',
            ],
            [
                'matricule'  => 'MAT-2024009',
                'nom'        => 'Laurent',
                'prenom'     => 'Céline',
                'telephone'  => '+33 6 66 77 88 99',
                'email'      => 'c.laurent@entreprise.fr',
                'direction'  => 'Marketing',
                'service'    => 'Communication',
                'poste'      => 'Chargée de communication',
                'statut'     => 'actif',
            ],
            [
                'matricule'  => 'MAT-2024010',
                'nom'        => 'Garcia',
                'prenom'     => 'Pablo',
                'telephone'  => '+33 6 44 55 66 77',
                'email'      => 'p.garcia@entreprise.fr',
                'direction'  => 'Logistique',
                'service'    => 'Transport',
                'poste'      => 'Chauffeur livreur',
                'statut'     => 'actif',
            ],
        ];

        foreach ($agents as $data) {
            // Crée le compte utilisateur lié à l'agent (rôle agent)
            $user = User::create([
                'name'      => $data['prenom'] . ' ' . $data['nom'],
                'email'     => $data['email'],
                'password'  => Hash::make('password'),
                'role'      => 'agent',
                'is_active' => $data['statut'] === 'actif',
            ]);

            Agent::create([
                'user_id'   => $user->id,
                'matricule' => $data['matricule'],
                'nom'       => $data['nom'],
                'prenom'    => $data['prenom'],
                'telephone' => $data['telephone'],
                'email'     => $data['email'],
                'direction' => $data['direction'],
                'service'   => $data['service'],
                'poste'     => $data['poste'],
                'statut'    => $data['statut'],
            ]);
        }
    }
}
