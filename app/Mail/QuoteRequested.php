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

class QuoteRequested extends Mailable
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

        // BCC admin for delivery proof, but only if it is not already the recipient or the from-address.
        $bcc = ($adminEmail
                && strcasecmp($adminEmail, $this->order->customer_email) !== 0
                && strcasecmp($adminEmail, $salesEmail) !== 0)
            ? [new Address($adminEmail, 'Hamid El Abassi')] : [];

        return new Envelope(
            subject: "Offerte-aanvraag {$this->order->order_number} ontvangen — DeSnipperaar",
            from: new Address($salesEmail, 'Team DeSnipperaar'),
            replyTo: $this->sender
                ? [new Address($this->sender->email, $this->sender->name)]
                : [new Address($salesEmail, 'Team DeSnipperaar')],
            bcc: $bcc,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quote-requested',
            with: ['order' => $this->order, 'sender' => $this->sender],
        );
    }
}
