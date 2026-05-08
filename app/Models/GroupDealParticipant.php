<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class GroupDealParticipant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'group_deal_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_postcode',
        'customer_address',
        'customer_city',
        'box_count',
        'container_count',
        'media_items',
        'notes',
        'price_snapshot',
        'order_id',
        'manage_token',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $p) {
            if (empty($p->manage_token)) {
                $p->manage_token = Str::random(32);
            }
        });
    }

    public function manageUrl(): string
    {
        return 'https://desnipperaar.nl/groepsdeals/manage/' . $this->manage_token;
    }

    protected $casts = [
        'media_items'    => 'array',
        'price_snapshot' => 'array',
        'box_count'      => 'integer',
        'container_count'=> 'integer',
    ];

    public function groupDeal()
    {
        return $this->belongsTo(GroupDeal::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function isOrganizer(): bool
    {
        return $this->id === $this->groupDeal?->organizer_participant_id;
    }
}
