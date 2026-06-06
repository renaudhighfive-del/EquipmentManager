<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PerteCasse extends Model
{
    use HasFactory;

    protected $table = 'pertes_casses';

    protected $fillable = [
        'equipement_id',
        'declare_par',
        'type',
        'date_declaration',
        'description',
        'statut',
        'valide_par',
        'date_validation',
        'motif_rejet',
    ];

    protected $casts = [
        'date_declaration' => 'date',
        'date_validation' => 'date',
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
}
