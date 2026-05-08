<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GroupDeal extends Model
{
    use HasFactory;

    const STATUS_DRAFT     = 'draft';
    const STATUS_OPEN      = 'open';
    const STATUS_CLOSED    = 'closed';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REJECTED  = 'rejected';

    protected $fillable = [
        'slug',
        'city',
        'pickup_date',
        'organizer_participant_id',
        'status',
        'approved_at',
        'closed_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'pickup_date'  => 'date',
        'approved_at'  => 'datetime',
        'closed_at'    => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function participants()
    {
        return $this->hasMany(GroupDealParticipant::class);
    }

    public function organizerParticipant()
    {
        return $this->belongsTo(GroupDealParticipant::class, 'organizer_participant_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function scopeOpen(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_OPEN);
    }

    public function scopePublic(Builder $q): Builder
    {
        return $q->whereIn('status', [self::STATUS_OPEN, self::STATUS_CLOSED, self::STATUS_COMPLETED]);
    }

    public function joinCutoffAt(): \Illuminate\Support\Carbon
    {
        $cutoffDays = (int) config('desnipperaar.group_deal.join_cutoff_days', 2);
        return $this->pickup_date->copy()->subDays($cutoffDays)->startOfDay();
    }

    public function joiningOpen(): bool
    {
        return $this->status === self::STATUS_OPEN
            && now() < $this->joinCutoffAt()
            && $this->participants()->count() < (int) config('desnipperaar.group_deal.max_joiners', 30);
    }

    public static function generateSlug(string $city, \DateTimeInterface $pickupDate): string
    {
        return Str::slug($city) . '-' . $pickupDate->format('Y-m-d');
    }
}
