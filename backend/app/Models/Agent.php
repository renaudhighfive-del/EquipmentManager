<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Agent extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'matricule',
        'nom',
        'prenom',
        'telephone',
        'email',
        'direction',
        'service',
        'poste',
        'statut',
    ];

    protected $appends = ['is_nouveau', 'nb_affectes', 'nb_perdus'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function affectations()
    {
        return $this->hasMany(Affectation::class);
    }

    public function pertesCasses()
    {
        return $this->hasMany(PerteCasse::class, 'declare_par', 'user_id');
    }

    /**
     * Accessor for is_nouveau
     */
    public function getIsNouveauAttribute(): bool
    {
        return $this->created_at >= Carbon::now()->subDays(30);
    }

    /**
     * Accessor for nb_affectes
     */
    public function getNbAffectesAttribute(): int
    {
        return $this->affectations()->where('statut', 'en_cours')->count();
    }

    /**
     * Accessor for nb_perdus
     */
    public function getNbPerdusAttribute(): int
    {
        return $this->pertesCasses()->where('statut', 'cloturee')->count();
    }
}
