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
 * Ontvangstbevestiging van een abonnementsaanvraag. Spiegelt QuoteRequested,
 * met eigen tekst: een abonnement is een terugkerende afspraak, geen losse
 * offerte, en de klant moet in de mail terugzien wat hij heeft gekozen.
 */
class SubscriptionRequested extends Mailable
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

        $subject = match ($this->mailLocale) {
            'en' => "Subscription request {$this->order->order_number} received — DeSnipperaar",
            'fr' => "Demande d'abonnement {$this->order->order_number} reçue — DeSnipperaar",
            'es' => "Solicitud de suscripción {$this->order->order_number} recibida — DeSnipperaar",
            default => "Abonnementsaanvraag {$this->order->order_number} ontvangen — DeSnipperaar",
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
            view: $this->mailLocale === 'nl' ? 'emails.subscription-requested' : 'emails.'.$this->mailLocale.'.subscription-requested',
            with: ['order' => $this->order, 'sender' => $this->sender],
        );
    }
}
