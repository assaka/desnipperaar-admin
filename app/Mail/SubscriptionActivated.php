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
 * De klant heeft de bevestiging geaccepteerd en het abonnement loopt vanaf nu.
 *
 * Dit is bewust niet OrderCreated. Die mail beschrijft één ophaling met een
 * ophaalmoment, en dat bestaat op dit punt nog niet: er is een looptijd, een
 * frequentie en een prijs, maar de eerste ophaaldatum spreken we daarna pas af.
 * Deze mail belooft dus alleen wat al vaststaat.
 */
class SubscriptionActivated extends Mailable
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
            'en' => "Your subscription {$this->order->order_number} is active — DeSnipperaar",
            'fr' => "Votre abonnement {$this->order->order_number} est actif — DeSnipperaar",
            'es' => "Su suscripción {$this->order->order_number} está activa — DeSnipperaar",
            default => "Je abonnement {$this->order->order_number} is actief — DeSnipperaar",
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
            view: $this->mailLocale === 'nl' ? 'emails.subscription-activated' : 'emails.'.$this->mailLocale.'.subscription-activated',
            with: ['order' => $this->order, 'sender' => $this->sender],
        );
    }
}
