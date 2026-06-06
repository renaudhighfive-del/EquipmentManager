<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserOtp extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'expires_at',
        'verified',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired()
    {
        return $this->expires_at->isPast();
    }
}
