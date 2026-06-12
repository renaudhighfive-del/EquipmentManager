<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class EquipementImage extends Model
{
    protected $fillable = ['equipement_id', 'path'];
    protected $appends = ['url'];

    public function equipement()
    {
        return $this->belongsTo(Equipement::class);
    }

    /**
     * Accessor for image URL
     */
    public function getUrlAttribute()
    {
        // En développement, utilisez Picsum pour avoir des images visibles
        if (app()->environment('local')) {
            // Utiliser le path comme seed pour avoir une image cohérente
            $seed = crc32($this->path);
            return "https://picsum.photos/seed/{$seed}/600/400";
        }

        // En production, utilisez Storage::url()
        return Storage::url($this->path);
    }
}
