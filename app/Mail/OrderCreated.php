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

    public function __construct(public Order $order, public ?User $sender = null)
    {
        $this->sender ??= $order->senderUser();
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

        return new Envelope(
            subject: "Orderbevestiging {$this->order->order_number} — DeSnipperaar",
            from: new Address($salesEmail, 'DeSnipperaar'),
            replyTo: $this->sender
                ? [new Address($this->sender->email, $this->sender->name)]
                : [new Address($salesEmail, 'DeSnipperaar')],
            bcc: $bcc,
        );
    }

    public function content(): Content
    {
        // Locked snapshot for group-deal materialized orders; live recompute otherwise.
        $snap = ($this->order->quote_locked && $this->order->price_snapshot)
            ? $this->order->price_snapshot
            : \App\Support\Pricing::snapshot(
                (int) $this->order->box_count,
                (int) $this->order->container_count,
                $this->order->media_items,
                (bool) $this->order->pilot,
                (bool) $this->order->first_box_free,
            );

        return new Content(
            view: 'emails.order-created',
            with: [
                'order'           => $this->order,
                'sender'          => $this->sender,
                'quote'           => [
                    'lines'            => $snap['lines'],
                    'subtotal'         => $snap['subtotal'] - array_sum(array_column($snap['media_lines'] ?? [], 'subtotal')),
                    'subtotal_regular' => $snap['subtotal_regular'] - array_sum(array_column($snap['media_lines'] ?? [], 'subtotal')),
                    'pilot'            => $snap['pilot'] ?? false,
                ],
                'mediaLines'      => $snap['media_lines'] ?? [],
                'subtotal'        => $snap['subtotal'],
                'subtotalRegular' => $snap['subtotal_regular'],
                'discount'        => $snap['discount'],
                'vat'             => $snap['vat'],
                'total'           => $snap['total'],
            ],
        );
    }
}
