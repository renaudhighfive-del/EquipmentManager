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
            'type_mouvement' => 'panne_declaree',
            'ancienne_valeur' => null,
            'nouvelle_valeur' => json_encode([
                'description' => $panne->description,
                'gravite' => $panne->gravite,
            ]),
            'reference_id' => $panne->id,
            'reference_type' => Panne::class,
        ]);
    }
}
