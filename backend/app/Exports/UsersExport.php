<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UsersExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return User::with(['agent'])->get();
    }

    /**
     * @var User $user
     */
    public function map($user): array
    {
        return [
            $user->id,
            $user->name,
            $user->email,
            $user->role,
            $user->is_active ? 'Actif' : 'Inactif',
            $user->agent ? $user->agent->matricule : '—',
            $user->agent ? $user->agent->prenom . ' ' . $user->agent->nom : '—',
            $user->created_at->format('d/m/Y H:i'),
        ];
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nom complet',
            'Email',
            'Rôle',
            'Statut',
            'Matricule agent',
            'Agent lié',
            'Date de création',
        ];
    }
}
