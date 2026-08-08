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
 * De uitnodiging om zelf een ophaalmoment te kiezen, met de link naar de
 * planpagina op desnipperaar.nl.
 *
 * Los van PickupConfirmed, want dit is het omgekeerde bericht. Daar staat een
 * moment vast en laten wij het weten; hier staat er nog niets en vragen wij de
 * klant om te kiezen. Voorlopig sturen wij hem met de hand vanuit de order, zodat
 * wij per klant kunnen zien of het werkt voordat er een knop in de
 * orderbevestiging komt.
 */
class PickupPlanInvite extends Mailable
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
        $subject = match ($this->mailLocale) {
            'en' => "Choose your pickup slot — {$this->order->order_number}",
            'fr' => "Choisissez votre créneau d'enlèvement — {$this->order->order_number}",
            'es' => "Elija su franja de recogida — {$this->order->order_number}",
            default => "Kies uw ophaalmoment — {$this->order->order_number}",
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
        $base = rtrim((string) config('desnipperaar.public_url'), '/');
        $prefix = $this->mailLocale === 'en' ? '/en' : '';

        return new Content(
            view: $this->mailLocale === 'nl' ? 'emails.pickup-plan-invite' : 'emails.'.$this->mailLocale.'.pickup-plan-invite',
            with: [
                'order'   => $this->order,
                'sender'  => $this->sender,
                // De planpagina bestaat in het Nederlands en het Engels. Frans en
                // Spaans krijgen de Nederlandse pagina; die kent hun taal nog niet,
                // en een link naar een pagina die bestaat is beter dan een 404.
                'planUrl' => $base.$prefix.'/plan/'.$this->order->public_token,
            ],
        );
    }
}
