<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Affectation extends Model
{
    use HasFactory;

    protected $fillable = [
        'equipement_id',
        'agent_id',
        'affecte_par',
        'date_affectation',
        'photo_remise',
        'date_retour',
        'etat_retour',
        'photo_retour',
        'observations',
        'statut',
    ];

    protected $casts = [
        'date_affectation' => 'date',
        'date_retour' => 'date',
    ];

    public function equipement()
    {
        return $this->belongsTo(Equipement::class);
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function affectePar()
    {
        return $this->belongsTo(User::class, 'affecte_par');
    }
}
