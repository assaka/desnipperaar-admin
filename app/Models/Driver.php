<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'license_last4',
        'vog_valid_until',
        'active',
    ];

    protected $casts = [
        'vog_valid_until' => 'date',
        'active' => 'boolean',
    ];

    public function bons()
    {
        return $this->hasMany(Bon::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function isVogExpiringSoon(int $days = 30): bool
    {
        return $this->vog_valid_until
            && $this->vog_valid_until->isBefore(now()->addDays($days));
    }
}
