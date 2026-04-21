<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'certificate_number',
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

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public static function generateCertificateNumber(): string
    {
        $prefix = config('desnipperaar.certificate.prefix');
        $year   = now()->year;

        $last = self::where('certificate_number', 'like', "{$prefix}-{$year}-%")
            ->orderByDesc('id')
            ->first();

        $seq = $last
            ? ((int) substr($last->certificate_number, -4)) + 1
            : config('desnipperaar.order.start');

        return sprintf('%s-%d-%04d', $prefix, $year, $seq);
    }
}
