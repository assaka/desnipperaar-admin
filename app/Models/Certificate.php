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
        'destruction_method',
        'operator_name',
        'operator_signature_path',
        'pdf_path',
        'emailed_at',
    ];

    protected $casts = [
        'destroyed_at' => 'datetime',
        'emailed_at'   => 'datetime',
        'weight_kg_final' => 'decimal:2',
    ];

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
}
