<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bon extends Model
{
    use HasFactory;

    const MODE_OPHAAL   = 'ophaal';
    const MODE_BRENG    = 'breng';
    const MODE_MOBIEL   = 'mobiel';

    protected $fillable = [
        'bon_number',
        'order_id',
        'driver_id',
        'driver_name_snapshot',
        'driver_license_last4',
        'mode',
        'actual_boxes',
        'actual_containers',
        'actual_media',
        'picked_up_at',
        'weight_kg',
        'notes',
        'customer_signature_path',
        'driver_signature_path',
    ];

    protected $casts = [
        'picked_up_at'     => 'datetime',
        'weight_kg'        => 'decimal:2',
        'actual_boxes'     => 'integer',
        'actual_containers'=> 'integer',
        'actual_media'     => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function seals()
    {
        return $this->hasMany(Seal::class);
    }

    public static function generateBonNumber(): string
    {
        $prefix = config('desnipperaar.bon.prefix');
        $year   = now()->year;

        $last = self::where('bon_number', 'like', "{$prefix}-{$year}-%")
            ->orderByDesc('id')
            ->first();

        $seq = $last
            ? ((int) substr($last->bon_number, -4)) + 1
            : config('desnipperaar.order.start');

        return sprintf('%s-%d-%04d', $prefix, $year, $seq);
    }
}
