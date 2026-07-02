<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderMessage extends Model
{
    protected $fillable = [
        'order_id', 'direction', 'channel', 'from_email', 'to_email',
        'subject', 'body_text', 'body_html', 'external_id', 'meta', 'occurred_at',
    ];

    protected $casts = [
        'meta'        => 'array',
        'occurred_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isInbound(): bool
    {
        return $this->direction === 'in';
    }
}
