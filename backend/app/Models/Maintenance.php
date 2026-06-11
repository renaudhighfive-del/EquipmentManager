<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Maintenance extends Model
{
    use HasFactory;

    protected $fillable = [
        'equipement_id',
        'panne_id',
        'responsable_id',
        'technicien',
        'type',
        'actions_effectuees',
        'cout',
        'date_debut',
        'date_fin',
        'photos_retour',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'cout' => 'decimal:2',
        'photos_retour' => 'json',
    ];

    public function equipement()
    {
        return $this->belongsTo(Equipement::class);
    }

    public function panne()
    {
        return $this->belongsTo(Panne::class);
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }
}
