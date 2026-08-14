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
 * Bevestiging dat een order is geannuleerd.
 *
 * Moet twee dingen zeggen, want daar zit de onzekerheid bij de klant: er komt
 * niemand langs, en er volgt geen rekening. Stond er een ophaalmoment gepland,
 * dan wordt dat expliciet genoemd, anders zet iemand op die dag zijn dozen
 * alsnog klaar.
 */
class OrderCancelled extends Mailable
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
            'en' => "Your order {$this->order->order_number} has been cancelled — DeSnipperaar",
            'fr' => "Votre commande {$this->order->order_number} est annulée — DeSnipperaar",
            'es' => "Su pedido {$this->order->order_number} ha sido cancelado — DeSnipperaar",
            default => "Je opdracht {$this->order->order_number} is geannuleerd — DeSnipperaar",
        };

        $subject .= ' '.$this->order->replyTag();

        return new Envelope(
            subject: $subject,
            from: new Address($salesEmail, 'DeSnipperaar'),
            replyTo: [new Address($salesEmail, 'DeSnipperaar')],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: $this->mailLocale === 'nl' ? 'emails.order-cancelled' : 'emails.'.$this->mailLocale.'.order-cancelled',
            with: [
                'order'  => $this->order,
                'sender' => $this->sender,
                'reason' => $this->order->cancel_reason,
            ],
        );
    }
}
