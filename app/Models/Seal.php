<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seal extends Model
{
    use HasFactory;

    protected $fillable = [
        'bon_id',
        'seal_number',
        'container_type',
        'closed_at_destruction',
        'destruction_note',
    ];

    protected $casts = [
        'closed_at_destruction' => 'datetime',
    ];

    public function bon()
    {
        return $this->belongsTo(Bon::class);
    }
}
