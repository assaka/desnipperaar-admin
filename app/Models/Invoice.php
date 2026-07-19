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
        'invoice_number', 'order_id', 'bon_id', 'period_start', 'period_end',
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
        'period_start'    => 'date',
        'period_end'      => 'date',
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

    /**
     * Bouw de factuur voor één abonnementsperiode, vooruit.
     *
     * Bewust los van fromBon(). Die herrekent alles uit de losse doos- en
     * containerprijzen via Pricing::quote() en negeert wat er is afgesproken.
     * Voor een abonnement is de afgesproken prijs (sub_price_excl_btw) juist het
     * enige dat telt, dus dit pad rekent niets opnieuw uit.
     *
     * Dit is ook het aanknopingspunt voor automatische incasso later: bedrag en
     * periode worden hier bepaald, het versturen en innen gebeurt erbuiten.
     */
    public static function fromSubscription(Order $order, \Carbon\Carbon $periodStart): self
    {
        $customer = $order->customer;
        $isYear   = $order->sub_term === 'jaar';

        $periodStart = $periodStart->copy()->startOfDay();
        $periodEnd   = $order->subPeriodEnd($periodStart);

        // Een opgezegd abonnement stopt midden in een periode. Verder factureren
        // dan de einddatum zou de klant laten betalen voor een container die al
        // is opgehaald.
        if ($order->sub_ends_on && $order->sub_ends_on->lessThan($periodEnd)) {
            $periodEnd = $order->sub_ends_on->copy()->startOfDay();
        }

        $price = (float) $order->sub_price_excl_btw;

        // Een gedeeltelijke periode rekenen we naar rato van de dagen. Bij een
        // maand is de noemer de lengte van die maand, niet de lengte van het
        // stuk dat we factureren: een start op de 15e is 17 van 31 dagen, geen
        // volle maand. Bij jaarbetaling is de noemer het hele jaar, zodat een
        // staartperiode na opzeggen nooit een vol jaar in rekening brengt.
        $daysInPeriod = $periodStart->diffInDays($periodEnd) + 1;
        $daysInFull   = $order->subPeriodNominalDays($periodStart);

        $amount = $daysInPeriod < $daysInFull
            ? round($price * $daysInPeriod / $daysInFull, 2)
            : $price;

        $label = sprintf(
            'Abonnement archiefvernietiging 240 L, %s (%s t/m %s)',
            $order->subFreqLabel(),
            $periodStart->format('d-m-Y'),
            $periodEnd->format('d-m-Y'),
        );
        if ($amount !== $price) {
            $label .= sprintf(', naar rato %d van %d dagen', $daysInPeriod, $daysInFull);
        }

        $lines = [[
            'label'    => $label,
            'qty'      => 1,
            'unit'     => $amount,
            'subtotal' => $amount,
        ]];

        // Slotfactuur van een Flex dat binnen twaalf maanden stopt: de retourrit
        // erbij, als eigen zichtbare regel.
        $isFinal = $order->sub_ends_on && $periodEnd->equalTo($order->sub_ends_on);
        if ($isFinal && $order->owesReturnCost()) {
            $cost = (float) config('desnipperaar.subscription.return_cost');
            $lines[] = [
                'label'    => 'Logistieke retourkosten container (opzegging binnen 12 maanden)',
                'qty'      => 1,
                'unit'     => $cost,
                'subtotal' => $cost,
            ];
        }

        $subtotal = round(array_sum(array_column($lines, 'subtotal')), 2);
        $vat      = round($subtotal * 0.21, 2);
        $total    = round($subtotal + $vat, 2);

        return self::create([
            'invoice_number'    => self::generateInvoiceNumber(),
            'order_id'          => $order->id,
            'bon_id'            => null,
            'period_start'      => $periodStart->toDateString(),
            'period_end'        => $periodEnd->toDateString(),
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

    /** Build an invoice from a signed bon — sole entry point. */
    public static function fromBon(Bon $bon): self
    {
        $order    = $bon->order;
        $customer = $order->customer;

        // Een ophaling onder een abonnement mag hier nooit langs. Deze methode
        // herrekent uit de losse doos- en containerprijzen, dus dat zou een
        // rekening van ruim honderd euro opleveren bovenop het maandbedrag dat
        // de klant al betaalt. De abonnementsfactuur loopt via fromSubscription().
        if ($order->isSubscriptionPickup()) {
            throw new \LogicException(
                "Order {$order->order_number} is een ophaling onder abonnement "
                . ($order->subscription?->order_number ?? $order->subscription_order_id)
                . ' en mag niet los gefactureerd worden.'
            );
        }

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
        // Richer invoice labels; pricing + staffel come from the central Pricing class.
        $mediaLabels = \App\Support\Pricing::MEDIA_LABELS_INVOICE;
        foreach ($media as $k => $q) {
            $line = \App\Support\Pricing::mediaLine($k, (int) $q);
            if ($line !== null) {
                $line['label'] = $mediaLabels[$k] ?? $line['label'];
                $lines[] = $line;
            }
        }

        // Pickup surcharge for the "sooner" option (0 for free pickup / regio Amsterdam).
        $pickupCost = (float) ($order->pickup_cost ?? 0);
        if ($pickupCost > 0) {
            $lines[] = [
                'label'    => 'Eerder ophalen (binnen 2 weken)',
                'qty'      => 1,
                'unit'     => $pickupCost,
                'subtotal' => $pickupCost,
            ];
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
