<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    const TYPE_DIRECT        = 'direct';
    const TYPE_QUOTE         = 'quote';

    const STATE_NIEUW        = 'nieuw';
    const STATE_BEVESTIGD    = 'bevestigd';
    const STATE_OPGEHAALD    = 'opgehaald';
    const STATE_VERNIETIGD   = 'vernietigd';
    const STATE_AFGESLOTEN   = 'afgesloten';

    protected $fillable = [
        'order_number',
        'type',
        'customer_id',
        'created_by_user_id',
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
        'quoted_amount_excl_btw',
        'quote_body',
        'quote_sent_at',
        'quote_valid_until',
        'quote_accepted_at',
        'quote_acceptance_ip',
        'quote_token',
    ];

    protected $casts = [
        'media_items' => 'array',
        'pilot' => 'boolean',
        'first_box_free' => 'boolean',
        'pickup_date' => 'date',
        'quote_sent_at' => 'datetime',
        'quote_valid_until' => 'date',
        'quote_accepted_at' => 'datetime',
        'quoted_amount_excl_btw' => 'decimal:2',
        'box_count' => 'integer',
        'container_count' => 'integer',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** Resolve the user whose name/email should appear as From: on mails about this order. */
    public function senderUser(): ?User
    {
        return $this->createdBy ?? User::orderBy('id')->first();
    }

    public function bons()
    {
        return $this->hasMany(Bon::class);
    }

    public function certificate()
    {
        return $this->hasOne(Certificate::class);
    }

    public function isQuoteExpired(): bool
    {
        return $this->quote_valid_until && $this->quote_valid_until->isPast();
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
