<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'certificate_number',
        'bon_id',
        'order_id',
        'destroyed_at',
        'weight_kg_final',
        'boxes_final',
        'containers_final',
        'destruction_method',
        'destruction_location',
        'operator_name',
        'operator_signature_path',
        'pdf_path',
        'emailed_at',
    ];

    protected $casts = [
        'destroyed_at' => 'datetime',
        'emailed_at'   => 'datetime',
        'weight_kg_final' => 'decimal:2',
        'boxes_final'      => 'integer',
        'containers_final' => 'integer',
    ];

    const LOC_DEPOT  = 'depot';
    const LOC_ONSITE = 'onsite';

    public function bon()
    {
        return $this->belongsTo(Bon::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public static function generateCertificateNumber(): string
    {
        return \App\Support\NumberSequence::next(
            config('desnipperaar.certificate.prefix'),
            (int) config('desnipperaar.order.start'),
        );
    }

    /**
     * Vernietigd op locatie van de klant? Een correctie op het certificaat gaat
     * voor. Anders volgt het de bon, wat bij een offerte de methode is die de
     * klant in het formulier koos en dus niet altijd wat er gebeurde.
     */
    public function isOnsite(): bool
    {
        if ($this->destruction_location) {
            return $this->destruction_location === self::LOC_ONSITE;
        }

        return $this->bon?->mode === Bon::MODE_MOBIEL;
    }

    /** Dozen: correctie, dan de werkelijke telling op de bon, dan de order. */
    public function boxCount(): int
    {
        if ($this->boxes_final !== null) {
            return (int) $this->boxes_final;
        }

        return (int) ($this->bon?->actual_boxes ?: $this->order->box_count);
    }

    public function containerCount(): int
    {
        if ($this->containers_final !== null) {
            return (int) $this->containers_final;
        }

        return (int) ($this->bon?->actual_containers ?: $this->order->container_count);
    }

    /**
     * De afgesproken regels van een offerte, voor als er geen dozen, containers
     * of datadragers te tellen zijn. Dan staat er tenminste wat er is afgesproken
     * in plaats van een lege tabel.
     *
     * @return array<int, array{qty: string, label: string}>
     */
    public function quoteMaterialLines(array $media): array
    {
        if ($this->boxCount() || $this->containerCount() || array_filter(array_map('intval', $media))) {
            return [];
        }

        return $this->order->quoteItemsToHaveReady();
    }
}
