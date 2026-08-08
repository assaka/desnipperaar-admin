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

/**
 * Het ophaalmoment, bevestigd of gewijzigd.
 *
 * Eén mail voor allebei, want wat erin staat is hetzelfde: wanneer wij komen,
 * waar, en wat de klant klaarzet. Alleen het onderwerp en de kop verschillen, en
 * bij een wijziging staat erbij welke afspraak komt te vervallen. Twee bijna
 * gelijke sjablonen per taal is vier keer dezelfde tekst op acht plekken.
 *
 * $previous is de vorige afspraak als leesbare tekst, of null bij een eerste
 * bevestiging.
 */
class PickupConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    public string $mailLocale;

    public function __construct(
        public Order $order,
        public ?User $sender = null,
        public ?string $previous = null,
    ) {
        $this->sender ??= $order->senderUser();
        $this->mailLocale = in_array($order->locale, ['nl', 'en', 'fr', 'es'], true) ? $order->locale : 'nl';
    }

    public function envelope(): Envelope
    {
        $subject = $this->previous
            ? match ($this->mailLocale) {
                'en' => "Pickup rescheduled — {$this->order->order_number}",
                'fr' => "Enlèvement modifié — {$this->order->order_number}",
                'es' => "Recogida modificada — {$this->order->order_number}",
                default => "Ophaalmoment gewijzigd — {$this->order->order_number}",
            }
            : match ($this->mailLocale) {
                'en' => "Pickup confirmed — {$this->order->order_number}",
                'fr' => "Enlèvement confirmé — {$this->order->order_number}",
                'es' => "Recogida confirmada — {$this->order->order_number}",
                default => "Ophaalmoment bevestigd — {$this->order->order_number}",
            };

        return new Envelope(
            subject: $subject,
            from: $this->sender
                ? new Address($this->sender->email, $this->sender->name)
                : null,
            replyTo: [new Address(config('desnipperaar.notifications.sales_email'), 'DeSnipperaar')],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: $this->mailLocale === 'nl' ? 'emails.pickup-confirmed' : 'emails.'.$this->mailLocale.'.pickup-confirmed',
            with: ['order' => $this->order, 'sender' => $this->sender, 'previous' => $this->previous],
        );
    }
}
