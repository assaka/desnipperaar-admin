<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DagAnnouncement extends Model
{
    protected $fillable = ['announced_on', 'code', 'recipients'];

    protected $casts = [
        'announced_on' => 'date',
    ];
}
