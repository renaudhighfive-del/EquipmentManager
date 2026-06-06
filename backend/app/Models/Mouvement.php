<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mouvement extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'equipement_id',
        'user_id',
        'type_mouvement',
        'ancienne_valeur',
        'nouvelle_valeur',
        'reference_id',
        'reference_type',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'ancienne_valeur' => 'json',
        'nouvelle_valeur' => 'json',
    ];

    public function equipement()
    {
        return $this->belongsTo(Equipement::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }
}
