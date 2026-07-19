<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Internal heads-up to the sales inbox when a customer submits a new quote
 * request or order. Sent FROM a system address (not sales@) so the copy is
 * actually delivered to the sales@ mailbox, and reply-to is the customer so
 * the team can answer directly from the alert.
 *
 * $kind: 'quote_request' | 'subscription_request' | 'new_order'
 */
class SalesAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public string $kind = 'new_order')
    {
    }

    public function envelope(): Envelope
    {
        $salesEmail = config('desnipperaar.notifications.sales_email');
        $label = match ($this->kind) {
            'quote_request'        => 'Nieuwe offerteaanvraag',
            'subscription_request' => 'Nieuwe abonnementsaanvraag',
            'subscription_active'  => 'Abonnement geactiveerd',
            default                => 'Nieuwe order',
        };
        $who = $this->order->customer_name ?: $this->order->customer_email;

        return new Envelope(
            subject: "{$label} {$this->order->order_number} · {$who}",
            from: new Address('noreply@desnipperaar.nl', 'DeSnipperaar Systeem'),
            to: [new Address($salesEmail, 'DeSnipperaar Sales')],
            replyTo: $this->order->customer_email
                ? [new Address($this->order->customer_email, $this->order->customer_name ?: $this->order->customer_email)]
                : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.sales-alert',
            with: [
                'order'    => $this->order,
                'kind'     => $this->kind,
                'orderUrl' => route('orders.show', $this->order),
            ],
        );
    }
}
