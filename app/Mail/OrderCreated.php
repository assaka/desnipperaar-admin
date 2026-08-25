<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderCreated extends Mailable
{
    use Queueable, SerializesModels;

    public string $mailLocale;

    public function __construct(public Order $order, public ?User $sender = null)
    {
        $this->sender ??= $order->senderUser();
        $this->mailLocale = in_array($order->locale, ['nl', 'en', 'fr', 'es'], true) ? $order->locale : 'nl';
    }

    public function envelope(): Envelope
    {
        $salesEmail = config('desnipperaar.notifications.sales_email');
        $adminEmail = config('desnipperaar.notifications.admin_email');

        // BCC admin so the team inbox gets a copy. Skip only if the customer is the same address
        // (avoid duplicate). Sales@ is allowed even when it equals From, since Resend does not
        // deliver outbound mail back to the From-mailbox.
        $bcc = ($adminEmail
                && strcasecmp($adminEmail, $this->order->customer_email) !== 0)
            ? [new Address($adminEmail, 'DeSnipperaar')] : [];

        $subject = match ($this->mailLocale) {
            'en' => "Order confirmation {$this->order->order_number} — DeSnipperaar",
            'fr' => "Confirmation de commande {$this->order->order_number} — DeSnipperaar",
            'es' => "Confirmación de pedido {$this->order->order_number} — DeSnipperaar",
            default => "Orderbevestiging {$this->order->order_number} — DeSnipperaar",
        };

        // Opaque reply reference so replies link back to this order's history.
        $subject .= ' '.$this->order->replyTag();

        return new Envelope(
            subject: $subject,
            from: new Address($salesEmail, 'DeSnipperaar'),
            replyTo: [new Address($salesEmail, 'DeSnipperaar')],
            bcc: $bcc,
        );
    }

    public function content(): Content
    {
        // Accepted custom quote: render the agreed itemised lines, not a box recompute.
        if (! empty($this->order->quote_lines)) {
            $lines = collect($this->order->quote_lines)->map(fn ($l) => [
                'label'    => $l['label'] ?? '',
                'qty'      => $l['qty'] ?? 1,
                'unit'     => $l['unit'] ?? 0,
                'subtotal' => $l['subtotal'] ?? 0,
            ])->all();
            $sub  = (float) ($this->order->quoted_amount_excl_btw ?? array_sum(array_column($lines, 'subtotal')));
            $vat  = round($sub * 0.21, 2);
            $snap = [
                'lines'            => $lines,
                'media_lines'      => [],
                'subtotal'         => $sub,
                'subtotal_regular' => $sub,
                'discount'         => 0,
                'vat'              => $vat,
                'total'            => round($sub + $vat, 2),
                'pilot'            => false,
            ];
        } else {
            // Locked snapshot for group-deal materialized orders; live recompute otherwise.
            $snap = ($this->order->quote_locked && $this->order->price_snapshot)
                ? $this->order->price_snapshot
                : \App\Support\Pricing::snapshot(
                    (int) $this->order->box_count,
                    (int) $this->order->container_count,
                    $this->order->media_items,
                    (bool) $this->order->pilot,
                    (bool) $this->order->first_box_free,
                    (float) $this->order->pickup_cost,
                    (float) $this->order->pickup_rush_fee,
                );
        }

        // Een kortingscode zit niet in Pricing::snapshot(), want die rekent uit
        // dozen en containers. Zonder deze stap zou de bevestiging het bedrag
        // van voor de korting noemen en dus een ander bedrag dan de factuur.
        $coupon = null;
        if ($this->order->hasCoupon()) {
            $gross  = (float) $snap['subtotal'];
            $amount = min(round((float) $this->order->coupon_discount, 2), $gross);

            if ($amount > 0) {
                // Via couponLine(), zodat de controle of "25% × € 55,00" ook echt
                // uitkomt op één plek staat en niet hier nog eens.
                $line = \App\Support\Pricing::couponLine(
                    $this->order->coupon_code,
                    $amount,
                    $this->order->coupon_type,
                    $this->order->coupon_value !== null ? (float) $this->order->coupon_value : null,
                    $gross,
                );

                $coupon = [
                    'code'   => $line['code'],
                    'amount' => $amount,
                    'pct'    => $line['pct'] ?? null,
                    'base'   => $line['base'] ?? null,
                ];

                $net           = round($gross - $amount, 2);
                $snap['vat']   = round($net * \App\Support\Pricing::VAT_RATE, 2);
                $snap['total'] = round($net + $snap['vat'], 2);
            }
        }

        return new Content(
            view: $this->mailLocale === 'nl' ? 'emails.order-created' : 'emails.'.$this->mailLocale.'.order-created',
            with: [
                'coupon'          => $coupon,
                'order'           => $this->order,
                'sender'          => $this->sender,
                'quote'           => [
                    'lines'            => $snap['lines'],
                    'subtotal'         => $snap['subtotal'] - array_sum(array_column($snap['media_lines'] ?? [], 'subtotal')),
                    'subtotal_regular' => $snap['subtotal_regular'] - array_sum(array_column($snap['media_lines'] ?? [], 'subtotal')),
                    'pilot'            => $snap['pilot'] ?? false,
                ],
                'mediaLines'      => $snap['media_lines'] ?? [],
                'pickupCost'      => $snap['pickup_cost'] ?? 0,
                'pickupRushFee'   => $snap['pickup_rush_fee'] ?? 0,
                // De gekozen ophaalsnelheid draagt de ophaalregel in het
                // besteloverzicht. Aan het bedrag hangen kon niet: "gratis" kost
                // nul, en "eerder" ook zodra de klant binnen de gratis straal
                // woont, en dan viel de regel weg. Handmatige orders hebben geen
                // keuze en krijgen dus geen ophaalregel.
                'pickupChoice'    => $this->order->delivery_mode === 'ophaal'
                    ? ($this->order->pickup_choice ?: null)
                    : null,
                // Binnen de gratis straal viel er niets te kiezen, dus daar
                // noemen wij niet de wachttijd van twee weken die er niet geldt.
                //
                // Dit leest de straal van vandaag en niet die van toen de order
                // binnenkwam. Het bedrag staat vast op de order, dus dat schuift
                // niet mee; hoogstens leest een oude bevestiging alsof de klant
                // altijd al binnen de straal woonde.
                'pickupInRegion'  => $this->order->pickup_km !== null
                    && $this->order->pickup_km <= \App\Support\Pricing::freeKm(),
                'subtotal'        => $snap['subtotal'],
                'subtotalRegular' => $snap['subtotal_regular'],
                'discount'        => $snap['discount'],
                'vat'             => $snap['vat'],
                'total'           => $snap['total'],
            ],
        );
    }
}
