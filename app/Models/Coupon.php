<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code', 'type', 'value', 'min_order_amount',
        'max_uses', 'times_used', 'expires_at', 'is_active', 'description',
    ];

    protected $casts = [
        'value'            => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'expires_at'       => 'datetime',
        'is_active'        => 'boolean',
    ];

    public function isValid(float $subtotal = 0): bool
    {
        if (! $this->is_active) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        if ($this->max_uses !== null && $this->times_used >= $this->max_uses) return false;
        if ($this->min_order_amount !== null && $subtotal < (float) $this->min_order_amount) return false;
        return true;
    }

    public function discountFor(float $subtotal): float
    {
        if ($this->type === 'percentage') {
            return round($subtotal * ((float) $this->value / 100), 2);
        }
        return min((float) $this->value, $subtotal);
    }

    public function incrementUsage(): void
    {
        $this->increment('times_used');
    }
}
