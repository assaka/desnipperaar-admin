<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    const STATUS_DRAFT    = 'draft';
    const STATUS_SENT     = 'sent';
    const STATUS_PAID     = 'paid';
    const STATUS_CANCELED = 'canceled';

    protected $fillable = [
        'invoice_number', 'order_id', 'bon_id',
        'customer_name', 'customer_company', 'customer_email',
        'customer_address', 'customer_postcode', 'customer_city',
        'lines',
        'amount_excl_btw', 'vat_rate', 'vat_amount', 'amount_incl_btw',
        'issued_at', 'due_at', 'sent_at', 'paid_at',
        'status', 'pdf_path',
    ];

    protected $casts = [
        'lines'           => 'array',
        'amount_excl_btw' => 'decimal:2',
        'vat_rate'        => 'decimal:3',
        'vat_amount'      => 'decimal:2',
        'amount_incl_btw' => 'decimal:2',
        'issued_at'       => 'date',
        'due_at'          => 'date',
        'sent_at'         => 'datetime',
        'paid_at'         => 'datetime',
    ];

    public function order()  { return $this->belongsTo(Order::class); }
    public function bon()    { return $this->belongsTo(Bon::class); }

    public static function generateInvoiceNumber(): string
    {
        $prefix = config('desnipperaar.invoice.prefix');
        $year   = now()->year;
        $start  = config('desnipperaar.invoice.start');

        $last = self::where('invoice_number', 'like', "{$prefix}-{$year}-%")
            ->orderByDesc('id')
            ->first();

        $seq = $last
            ? ((int) substr($last->invoice_number, -4)) + 1
            : $start;

        return sprintf('%s-%d-%04d', $prefix, $year, $seq);
    }

    /** Build an invoice from a signed bon — sole entry point. */
    public static function fromBon(Bon $bon): self
    {
        $order    = $bon->order;
        $customer = $order->customer;

        $boxes      = $bon->actual_boxes      ?? $order->box_count;
        $containers = $bon->actual_containers ?? $order->container_count;
        $media      = !empty($bon->actual_media) ? $bon->actual_media : ($order->media_items ?? []);

        $quote = \App\Support\Pricing::quote(
            (int) $boxes,
            (int) $containers,
            (bool) $order->pilot,
            (bool) $order->first_box_free,
        );

        $lines = $quote['lines'];

        // Add media lines.
        $mediaPrices = ['hdd' => 9, 'ssd' => 15, 'usb' => 6, 'phone' => 12, 'laptop' => 19];
        $mediaLabels = ['hdd' => 'HDD / harde schijf', 'ssd' => 'SSD / NVMe', 'usb' => 'USB-stick / SD',
                        'phone' => 'Telefoon / tablet', 'laptop' => 'Laptop'];
        foreach ($media as $k => $q) {
            $q = (int) $q;
            if ($q > 0 && isset($mediaPrices[$k])) {
                $lines[] = [
                    'label'    => $mediaLabels[$k] ?? ucfirst($k),
                    'qty'      => $q,
                    'unit'     => $mediaPrices[$k],
                    'subtotal' => $mediaPrices[$k] * $q,
                ];
            }
        }

        $subtotal = array_sum(array_column($lines, 'subtotal'));
        $vat      = round($subtotal * 0.21, 2);
        $total    = round($subtotal + $vat, 2);

        return self::create([
            'invoice_number'    => self::generateInvoiceNumber(),
            'order_id'          => $order->id,
            'bon_id'            => $bon->id,
            'customer_name'     => $order->customer_name,
            'customer_company'  => $customer?->company,
            'customer_email'    => $order->customer_email,
            'customer_address'  => $order->customer_address,
            'customer_postcode' => $order->customer_postcode,
            'customer_city'     => $order->customer_city,
            'lines'             => $lines,
            'amount_excl_btw'   => $subtotal,
            'vat_rate'          => 0.21,
            'vat_amount'        => $vat,
            'amount_incl_btw'   => $total,
            'issued_at'         => now()->toDateString(),
            'due_at'            => now()->addDays(config('desnipperaar.invoice.payment_terms_days'))->toDateString(),
            'status'            => self::STATUS_DRAFT,
        ]);
    }
}
