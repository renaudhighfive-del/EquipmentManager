<?php

namespace App\Observers;

use App\Models\Panne;
use App\Models\Mouvement;
use Illuminate\Support\Facades\Auth;

class PanneObserver
{
    /**
     * Handle the Panne "created" event.
     */
    public function created(Panne $panne): void
    {
        Mouvement::create([
            'equipement_id' => $panne->equipement_id,
            'user_id' => Auth::id() ?? $panne->declare_par,
            'type_mouvement' => 'panne',
            'ancienne_valeur' => json_encode(['etat' => $panne->equipement->etat]),
            'nouvelle_valeur' => json_encode([
                'statut' => 'declaree',
                'description' => $panne->description,
                'gravite' => $panne->gravite,
            ]),
            'reference_id' => $panne->id,
            'reference_type' => Panne::class,
        ]);

        // Mettre à jour l'état de l'équipement
        $panne->equipement->update(['etat' => 'en_panne']);
    }

    /**
     * Handle the Panne "updated" event.
     */
    public function updated(Panne $panne): void
    {
        if ($panne->isDirty('statut')) {
            Mouvement::create([
                'equipement_id' => $panne->equipement_id,
                'user_id' => Auth::id() ?? 1,
                'type_mouvement' => 'panne',
                'ancienne_valeur' => json_encode(['statut' => $panne->getOriginal('statut')]),
                'nouvelle_valeur' => json_encode(['statut' => $panne->statut]),
                'reference_id' => $panne->id,
                'reference_type' => Panne::class,
            ]);

            // Si résolue, on remet l'équipement en service (ou neuf s'il n'avait jamais été affecté?)
            // En général, s'il y a une panne, c'est qu'il était en service.
            if ($panne->statut === 'resolue') {
                // Vérifier s'il y a d'autres pannes actives
                $otherActivePannes = Panne::where('equipement_id', $panne->equipement_id)
                    ->where('id', '!=', $panne->id)
                    ->whereNotIn('statut', ['resolue', 'irrecuperable'])
                    ->exists();

                if (!$otherActivePannes) {
                    $panne->equipement->update(['etat' => 'en_service']);
                }
            } elseif ($panne->statut === 'irrecuperable') {
                $panne->equipement->update(['etat' => 'reforme']);
            } elseif ($panne->statut === 'en_maintenance') {
                $panne->equipement->update(['etat' => 'en_maintenance']);
            }
        }
    }
}
