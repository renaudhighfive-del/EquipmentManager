<?php

namespace App\Exports;

use App\Models\Agent;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AgentsExport implements FromCollection, WithHeadings, WithMapping
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Agent::with('user')->get();
    }

    /**
    * @var Agent $agent
    */
    public function map($agent): array
    {
        return [
            $agent->matricule,
            $agent->prenom,
            $agent->nom,
            $agent->email,
            $agent->telephone,
            $agent->direction,
            $agent->service,
            $agent->poste,
            $agent->statut === 'actif' ? 'Actif' : 'Inactif',
            $agent->user ? 'Oui' : 'Non',
            $agent->created_at->format('d/m/Y'),
        ];
    }

    public function headings(): array
    {
        return [
            'Matricule',
            'Prénom',
            'Nom',
            'Email',
            'Téléphone',
            'Direction',
            'Service',
            'Poste',
            'Statut',
            'Compte Utilisateur',
            'Date de Création',
        ];
    }
}
