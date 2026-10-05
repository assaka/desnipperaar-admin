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
 * De klant klikte in de ophaalmail op "Ja, dit schikt". Deze mail bevestigt dat
 * het ophaalmoment daarmee definitief vaststaat.
 *
 * Eén sjabloon voor de vier talen, met de teksten in een tabel bovenin. Het is
 * een korte mail en vier bijna lege bestanden zouden alleen uit elkaar lopen.
 */
class PickupFinalized extends Mailable
{
    use Queueable, SerializesModels;

    public string $mailLocale;

    public function __construct(public Order $order)
    {
        $this->mailLocale = in_array($order->locale, ['nl', 'en', 'fr', 'es'], true) ? $order->locale : 'nl';
    }

    public function envelope(): Envelope
    {
        $subject = match ($this->mailLocale) {
            'en' => "Pickup finalised — {$this->order->order_number}",
            'fr' => "Enlèvement définitivement confirmé — {$this->order->order_number}",
            'es' => "Recogida confirmada definitivamente — {$this->order->order_number}",
            default => "Ophaalmoment definitief bevestigd — {$this->order->order_number}",
        };

        return new Envelope(
            subject: $subject,
            from: new Address(config('desnipperaar.notifications.sales_email'), 'DeSnipperaar'),
            replyTo: [new Address(config('desnipperaar.notifications.sales_email'), 'DeSnipperaar')],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.pickup-finalized',
            with: ['order' => $this->order, 'lang' => $this->mailLocale],
        );
    }
}
