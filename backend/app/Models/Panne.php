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
        'date_declaration',
        'description',
        'gravite',
        'statut',
        'photos',
    ];

    protected $casts = [
        'date_declaration' => 'date',
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

    public function maintenances()
    {
        return $this->hasMany(Maintenance::class);
    }
}
