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
 * Eén maand voor het einde van een Vast- of Jaartermijn.
 *
 * De mail moet vooral duidelijk maken wat er gebeurt als de klant niets doet,
 * want dat is wat meestal gebeurt. Stilzwijgend nog een jaar vooruit
 * factureren mag niet: de site belooft dat het daarna maandelijks doorloopt en
 * altijd opzegbaar is. Verlengen is dus een keuze die de klant zelf maakt, per
 * antwoord op deze mail.
 */
class SubscriptionRenewalNotice extends Mailable
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
            'en' => "Your subscription {$this->order->order_number} renews soon — DeSnipperaar",
            'fr' => "Votre abonnement {$this->order->order_number} arrive à échéance — DeSnipperaar",
            'es' => "Su suscripción {$this->order->order_number} vence pronto — DeSnipperaar",
            default => "Je termijn voor abonnement {$this->order->order_number} loopt af — DeSnipperaar",
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
        $monthly = config("desnipperaar.subscription.prices.vast.{$this->order->sub_freq}");
        $yearly  = config("desnipperaar.subscription.prices.jaar.{$this->order->sub_freq}");

        return new Content(
            view: $this->mailLocale === 'nl' ? 'emails.subscription-renewal' : 'emails.'.$this->mailLocale.'.subscription-renewal',
            with: [
                'order'        => $this->order,
                'sender'       => $this->sender,
                'renewalDate'  => $this->order->subRenewalDate(),
                'monthlyPrice' => $monthly,
                'yearlyPrice'  => $yearly,
            ],
        );
    }
}
