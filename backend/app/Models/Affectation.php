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
        'motif_rejet',
    ];

    protected $casts = [
        'date_affectation' => 'date',
        'date_retour' => 'date',
    ];

    protected $appends = ['photo_remise_url', 'photo_retour_url'];

    public function getPhotoRemiseUrlAttribute()
    {
        return $this->photo_remise ? asset('storage/' . $this->photo_remise) : null;
    }

    public function getPhotoRetourUrlAttribute()
    {
        return $this->photo_retour ? asset('storage/' . $this->photo_retour) : null;
    }

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

    public function mouvements()
    {
        return $this->morphMany(Mouvement::class, 'reference');
    }
}
