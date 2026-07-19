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
 * De vaste ophaaldag is gewijzigd.
 *
 * Dit moet gemaild worden, want de klant zet zijn container klaar op de dag die
 * hij kent. Verandert die dag zonder bericht, dan staat de container op de
 * verkeerde dag buiten of juist niet. De eerstvolgende ophaaldatum staat er
 * daarom concreet in, niet alleen de nieuwe weekdag.
 */
class SubscriptionPickupDayChanged extends Mailable
{
    use Queueable, SerializesModels;

    public string $mailLocale;

    public function __construct(
        public Order $order,
        public string $previousDayLabel,
        public ?User $sender = null,
    ) {
        $this->sender ??= $order->senderUser();
        $this->mailLocale = in_array($order->locale, ['nl', 'en', 'fr', 'es'], true) ? $order->locale : 'nl';
    }

    public function envelope(): Envelope
    {
        $salesEmail = config('desnipperaar.notifications.sales_email');

        $subject = match ($this->mailLocale) {
            'en' => "Your pickup day has changed — DeSnipperaar",
            'fr' => "Votre jour d'enlèvement a changé — DeSnipperaar",
            'es' => "Su día de recogida ha cambiado — DeSnipperaar",
            default => "Je vaste ophaaldag is gewijzigd — DeSnipperaar",
        };

        $subject .= ' '.$this->order->replyTag();

        return new Envelope(
            subject: $subject,
            from: new Address($salesEmail, 'DeSnipperaar'),
            replyTo: $this->sender
                ? [new Address($this->sender->email, $this->sender->name)]
                : [new Address($salesEmail, 'DeSnipperaar')],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: $this->mailLocale === 'nl' ? 'emails.subscription-pickup-day' : 'emails.'.$this->mailLocale.'.subscription-pickup-day',
            with: [
                'order'      => $this->order,
                'sender'     => $this->sender,
                'previous'   => $this->previousDayLabel,
                'nextPickup' => $this->order->nextPickupDate(),
            ],
        );
    }
}
