<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Interne melding: de klant klikte in de ophaalmail op "komt niet uit". Het moment
 * staat nog op de order; wij bellen of mailen de klant en plannen opnieuw.
 */
class PickupReceiptDeclined extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Ophaalmoment komt niet uit {$this->order->order_number} — {$this->order->customer_name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.pickup-receipt-declined',
            with: ['order' => $this->order],
        );
    }
}
