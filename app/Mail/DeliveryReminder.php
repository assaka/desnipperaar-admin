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
 * Herinnering de dag voordat wij de container komen brengen.
 *
 * Bewust een eigen mail en niet PickupReminder: die zegt dat wij komen ophalen
 * en vraagt de container buiten te zetten. Bij een bezorging is er nog geen
 * container, dus die tekst zou de klant op het verkeerde been zetten.
 *
 * De eerste ophaaldatum staat erin, zodat de klant meteen weet hoeveel tijd hij
 * heeft om te vullen.
 */
class DeliveryReminder extends Mailable
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
        $date = $this->order->pickup_date?->format('d-m-Y');

        $subject = match ($this->mailLocale) {
            'en' => "Reminder: we deliver your container on {$date} — DeSnipperaar",
            'fr' => "Rappel : livraison de votre conteneur le {$date} — DeSnipperaar",
            'es' => "Recordatorio: entregamos su contenedor el {$date} — DeSnipperaar",
            default => "Herinnering: wij brengen uw container op {$date} — DeSnipperaar",
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
        $sub = $this->order->subscription;

        return new Content(
            view: $this->mailLocale === 'nl' ? 'emails.delivery-reminder' : 'emails.'.$this->mailLocale.'.delivery-reminder',
            with: [
                'order'        => $this->order,
                'sender'       => $this->sender,
                'subscription' => $sub,
                'firstPickup'  => $sub?->nextPickupDate(),
            ],
        );
    }
}
