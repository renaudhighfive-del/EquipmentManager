<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Panne extends Model
{
    use HasFactory;

    protected $fillable = [
        'equipement_id',
        'declare_par',
        'valide_par',
        'date_declaration',
        'date_validation',
        'description',
        'gravite',
        'statut',
        'photos',
    ];

    protected $casts = [
        'date_declaration' => 'date',
        'date_validation' => 'datetime',
        'photos' => 'json',
    ];

    public function equipement()
    {
        return $this->belongsTo(Equipement::class);
    }

    public function declarePar()
    {
        return $this->belongsTo(User::class, 'declare_par');
    }

    public function validePar()
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    public function maintenances()
    {
        return $this->hasMany(Maintenance::class);
    }
}
