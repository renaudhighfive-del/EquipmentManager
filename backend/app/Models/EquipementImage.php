<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipementImage extends Model
{
    protected $fillable = ['equipement_id', 'path'];

    public function equipement()
    {
        return $this->belongsTo(Equipement::class);
    }
}
