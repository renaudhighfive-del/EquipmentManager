<?php

namespace App\Observers;

use App\Models\Affectation;
use App\Models\Mouvement;
use Illuminate\Support\Facades\Auth;

class AffectationObserver
{
    /**
     * Handle the Affectation "created" event.
     */
    public function created(Affectation $affectation): void
    {
        Mouvement::create([
            'equipement_id' => $affectation->equipement_id,
            'user_id' => Auth::id() ?? $affectation->affecte_par,
            'type_mouvement' => 'affectation',
            'ancienne_valeur' => null,
            'nouvelle_valeur' => json_encode([
                'agent_id' => $affectation->agent_id,
                'date_affectation' => $affectation->date_affectation,
            ]),
            'reference_id' => $affectation->id,
            'reference_type' => Affectation::class,
        ]);
    }

    /**
     * Handle the Affectation "updated" event.
     */
    public function updated(Affectation $affectation): void
    {
        if ($affectation->isDirty('statut') && $affectation->statut === 'retourne') {
            // Créer le mouvement de retour
            Mouvement::create([
                'equipement_id' => $affectation->equipement_id,
                'user_id' => Auth::id() ?? 1,
                'type_mouvement' => 'retour',
                'ancienne_valeur' => json_encode(['statut' => 'en_cours']),
                'nouvelle_valeur' => json_encode([
                    'statut' => 'retourne',
                    'date_retour' => $affectation->date_retour,
                    'etat_retour' => $affectation->etat_retour,
                ]),
                'reference_id' => $affectation->id,
                'reference_type' => Affectation::class,
            ]);

            // Remettre l'équipement en état 'neuf' (ou disponible) pour qu'il puisse être réaffecté
            $affectation->equipement->update(['etat' => 'neuf']);
        }
    }
}
