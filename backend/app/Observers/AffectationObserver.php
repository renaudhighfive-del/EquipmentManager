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
        // Créer le mouvement d'affectation
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

        // Mettre à jour l'équipement à "en_service"
        $affectation->equipement->update(['etat' => 'en_service']);
    }

    /**
     * Handle the Affectation "updated" event.
     */
    public function updated(Affectation $affectation): void
    {
        // Vérifier si le statut a changé
        if ($affectation->isDirty('statut')) {
            $ancienStatut = $affectation->getOriginal('statut');
            $nouveauStatut = $affectation->statut;

            switch ($nouveauStatut) {
                case 'confirmee':
                    $this->creerMouvement($affectation, 'reception_confirmee', $ancienStatut);
                    break;

                case 'retour_en_attente':
                    $this->creerMouvement($affectation, 'retour_demande', $ancienStatut);
                    break;

                case 'retourne':
                    $this->creerMouvement($affectation, 'retour_valide', $ancienStatut);
                    // Remettre l'équipement en état 'neuf' (disponible pour réaffectation)
                    $affectation->equipement->update(['etat' => 'neuf']);
                    break;
            }
        }
    }

    /**
     * Créer un mouvement avec les valeurs appropriées
     */
    protected function creerMouvement(Affectation $affectation, string $typeMouvement, ?string $ancienStatut): void
    {
        Mouvement::create([
            'equipement_id' => $affectation->equipement_id,
            'user_id' => Auth::id(),
            'type_mouvement' => $typeMouvement,
            'ancienne_valeur' => json_encode(['statut' => $ancienStatut]),
            'nouvelle_valeur' => json_encode([
                'statut' => $affectation->statut,
                'date_retour' => $affectation->date_retour,
                'etat_retour' => $affectation->etat_retour,
            ]),
            'reference_id' => $affectation->id,
            'reference_type' => Affectation::class,
        ]);
    }
}
