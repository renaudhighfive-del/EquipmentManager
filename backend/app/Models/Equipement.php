<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Equipement extends Model
{
    use HasFactory;

    protected $with = ['images']; // Charge les images par défaut

    protected $fillable = [
        'categorie_id',
        'reference',
        'numero_serie',
        'imei',
        'code_inventaire',
        'marque',
        'modele',
        'fournisseur',
        'date_acquisition',
        'prix_achat',
        'garantie_fin',
        'etat',
        'localisation',
        'notes',
        'is_archived',
    ];

    protected $casts = [
        'date_acquisition' => 'date',
        'garantie_fin' => 'date',
        'is_archived' => 'boolean',
        'prix_achat' => 'decimal:2',
    ];

    protected $appends = ['is_nouveau'];

    public function categorie()
    {
        return $this->belongsTo(Categorie::class);
    }

    public function affectations()
    {
        return $this->hasMany(Affectation::class);
    }

    public function currentAffectation()
    {
        return $this->hasOne(Affectation::class)->where('statut', 'confirmee')->latestOfMany();
    }

    public function pendingAffectation()
    {
        return $this->hasOne(Affectation::class)->where('statut', 'en_cours')->latestOfMany();
    }

    public function pannes()
    {
        return $this->hasMany(Panne::class);
    }

    public function maintenances()
    {
        return $this->hasMany(Maintenance::class);
    }

    public function mouvements()
    {
        return $this->hasMany(Mouvement::class);
    }

    public function images()
    {
        return $this->hasMany(EquipementImage::class);
    }

    /**
     * Accessor for is_nouveau
     */
    public function getIsNouveauAttribute(): bool
    {
        if (is_null($this->date_acquisition)) {
            return false;
        }
        return Carbon::parse($this->date_acquisition) >= Carbon::now()->subDays(30);
    }
}
