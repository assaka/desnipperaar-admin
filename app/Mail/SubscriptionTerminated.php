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
 * Bevestiging van een opzegging.
 *
 * Moet twee dingen glashelder maken, want daar gaat het bij opzeggingen mis:
 * tot wanneer het abonnement doorloopt (en dus doorgefactureerd wordt), en of er
 * retourkosten bij komen. Allebei staan ze met bedrag en datum in de mail, zodat
 * de klant later niet verrast wordt door een factuur die hij niet verwachtte.
 */
class SubscriptionTerminated extends Mailable
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
            'en' => "Your subscription {$this->order->order_number} has been cancelled — DeSnipperaar",
            'fr' => "Votre abonnement {$this->order->order_number} est résilié — DeSnipperaar",
            'es' => "Su suscripción {$this->order->order_number} ha sido cancelada — DeSnipperaar",
            default => "Je abonnement {$this->order->order_number} is opgezegd — DeSnipperaar",
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
            view: $this->mailLocale === 'nl' ? 'emails.subscription-terminated' : 'emails.'.$this->mailLocale.'.subscription-terminated',
            with: [
                'order'       => $this->order,
                'sender'      => $this->sender,
                'endsOn'      => $this->order->sub_ends_on,
                'returnCost'  => $this->order->owesReturnCost()
                    ? (float) config('desnipperaar.subscription.return_cost')
                    : null,
                'lastPickup'  => $this->order->pickups()
                    ->whereDate('pickup_date', '<=', $this->order->sub_ends_on?->toDateString() ?? now()->toDateString())
                    ->orderByDesc('pickup_date')
                    ->first(),
            ],
        );
    }
}
