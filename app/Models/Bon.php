<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bon extends Model
{
    use HasFactory;

    const MODE_OPHAAL   = 'ophaal';
    const MODE_BRENG    = 'breng';     // klant brengt zelf (brengservice)
    const MODE_MOBIEL   = 'mobiel';
    /** Wij brengen een container. Andere richting dan MODE_BRENG. */
    const MODE_BEZORGING = 'bezorging';
    /** Wij halen de container weer op, aan het eind van een abonnement. */
    const MODE_RETOUR    = 'retour';

    /** Ritten waarbij niets wordt meegenomen, dus ook niets wordt vernietigd. */
    const MODES_ZONDER_VERNIETIGING = [self::MODE_BEZORGING, self::MODE_RETOUR];

    protected $fillable = [
        'bon_number',
        'order_id',
        'driver_id',
        'driver_name_snapshot',
        'driver_license_last4',
        'mode',
        'planned_for',
        'planned_window',
        'scheduled_for',
        'reminder_sent_at',
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
        'planned_for'      => 'date',
        'scheduled_for'    => 'date',
        'reminder_sent_at' => 'datetime',
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

    public function certificate()
    {
        return $this->hasOne(Certificate::class);
    }

    /** Bij een bezorging of retour wordt er niets vernietigd, dus geen certificaat. */
    public function levertCertificaat(): bool
    {
        return ! in_array($this->mode, self::MODES_ZONDER_VERNIETIGING, true);
    }

    /** Is deze rit gereden? De handtekening bepaalt dat, niet de planning. */
    public function isGereden(): bool
    {
        return $this->picked_up_at !== null;
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
        return \App\Support\NumberSequence::next(
            config('desnipperaar.bon.prefix'),
            (int) config('desnipperaar.order.start'),
        );
    }
}
