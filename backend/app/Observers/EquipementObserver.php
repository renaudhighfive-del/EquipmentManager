<?php

namespace App\Observers;

use App\Models\Equipement;
use App\Models\Mouvement;
use Illuminate\Support\Facades\Auth;

class EquipementObserver
{
    /**
     * Handle the Equipement "updated" event.
     */
    public function updated(Equipement $equipement): void
    {
        if ($equipement->isDirty('etat')) {
            Mouvement::create([
                'equipement_id' => $equipement->id,
                'user_id' => Auth::id() ?? 1, // Fallback for system actions
                'type_mouvement' => $this->determineMovementType($equipement),
                'ancienne_valeur' => json_encode(['etat' => $equipement->getOriginal('etat')]),
                'nouvelle_valeur' => json_encode(['etat' => $equipement->etat]),
                'reference_id' => $equipement->id,
                'reference_type' => Equipement::class,
            ]);
        }
    }

    private function determineMovementType(Equipement $equipement): string
    {
        $newEtat = $equipement->etat;
        
        return match ($newEtat) {
            'en_attente_sinistre' => 'sinistre_declare',
            'perdu' => 'perte_declaree',
            'reforme' => 'reforme',
            default => 'changement_etat',
        };
    }
}
