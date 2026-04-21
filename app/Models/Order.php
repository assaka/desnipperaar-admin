<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    const STATE_NIEUW        = 'nieuw';
    const STATE_BEVESTIGD    = 'bevestigd';
    const STATE_OPGEHAALD    = 'opgehaald';
    const STATE_VERNIETIGD   = 'vernietigd';
    const STATE_AFGESLOTEN   = 'afgesloten';

    protected $fillable = [
        'order_number',
        'customer_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_address',
        'customer_postcode',
        'customer_city',
        'customer_reference',
        'delivery_mode',
        'box_count',
        'container_count',
        'media_items',
        'notes',
        'state',
        'pilot',
        'pickup_date',
        'pickup_window',
        'first_box_free',
    ];

    protected $casts = [
        'media_items' => 'array',
        'pilot' => 'boolean',
        'first_box_free' => 'boolean',
        'pickup_date' => 'date',
        'box_count' => 'integer',
        'container_count' => 'integer',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function bons()
    {
        return $this->hasMany(Bon::class);
    }

    public function certificate()
    {
        return $this->hasOne(Certificate::class);
    }

    public static function generateOrderNumber(): string
    {
        $prefix = config('desnipperaar.order.prefix');
        $year   = now()->year;
        $start  = config('desnipperaar.order.start');

        $last = self::where('order_number', 'like', "{$prefix}-{$year}-%")
            ->orderByDesc('id')
            ->first();

        $seq = $last
            ? ((int) substr($last->order_number, -4)) + 1
            : $start;

        return sprintf('%s-%d-%04d', $prefix, $year, $seq);
    }
}
