<?php

namespace App\Observers;

use App\Models\Maintenance;
use App\Models\Mouvement;
use Illuminate\Support\Facades\Auth;

class MaintenanceObserver
{
    /**
     * Handle the Maintenance "created" event.
     */
    public function created(Maintenance $maintenance): void
    {
        Mouvement::create([
            'equipement_id' => $maintenance->equipement_id,
            'user_id' => Auth::id() ?? $maintenance->responsable_id,
            'type_mouvement' => 'maintenance_debut',
            'ancienne_valeur' => null,
            'nouvelle_valeur' => json_encode([
                'type' => $maintenance->type,
                'technicien' => $maintenance->technicien,
                'date_debut' => $maintenance->date_debut,
            ]),
            'reference_id' => $maintenance->id,
            'reference_type' => Maintenance::class,
        ]);
    }

    /**
     * Handle the Maintenance "updated" event.
     */
    public function updated(Maintenance $maintenance): void
    {
        if ($maintenance->isDirty('date_fin') && !is_null($maintenance->date_fin)) {
            Mouvement::create([
                'equipement_id' => $maintenance->equipement_id,
                'user_id' => Auth::id() ?? 1,
                'type_mouvement' => 'maintenance_fin',
                'ancienne_valeur' => json_encode(['date_fin' => null]),
                'nouvelle_valeur' => json_encode([
                    'date_fin' => $maintenance->date_fin,
                    'diagnostic' => $maintenance->diagnostic,
                    'actions' => $maintenance->actions_effectuees,
                ]),
                'reference_id' => $maintenance->id,
                'reference_type' => Maintenance::class,
            ]);
        }
    }
}
