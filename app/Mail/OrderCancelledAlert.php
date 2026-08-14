<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Interne melding: een order is geannuleerd.
 *
 * Annuleren gebeurt in de admin en is daarmee alleen zichtbaar voor wie er op
 * dat moment naar keek. Rijdt er iemand anders de planning, dan hoort die het
 * pas als hij de order toevallig opnieuw opent. Vandaar deze melding, met de
 * gevolgen erbij: welke ritten zijn vervallen en welke facturen niet meer
 * betaald hoeven te worden.
 *
 * Gaat altijd uit, ook als de klant niet is gemaild. Wij moeten het weten, ook
 * bij een dubbele bestelling of een test.
 */
class OrderCancelledAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public ?User $canceledBy = null,
        public int $droppedBons = 0,
        public int $voidedInvoices = 0,
        public bool $customerNotified = false,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Order {$this->order->order_number} geannuleerd — {$this->order->customer_name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-cancelled-alert',
            with: [
                'order'            => $this->order,
                'canceledBy'       => $this->canceledBy,
                'droppedBons'      => $this->droppedBons,
                'voidedInvoices'   => $this->voidedInvoices,
                'customerNotified' => $this->customerNotified,
            ],
        );
    }
}
