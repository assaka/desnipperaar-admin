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

class QuoteSent extends Mailable
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
        // No amount => this is just a message (extra info / a question from our side),
        // not a finished quote. Frame the subject accordingly. Keep the order number
        // in every subject so client replies link back via FetchInboundMail.
        $isOffer = ! is_null($this->order->quoted_amount_excl_btw);

        $subject = $isOffer
            ? match ($this->mailLocale) {
                'en' => "Quote {$this->order->order_number} — review and accept",
                'fr' => "Devis {$this->order->order_number} — vérifier et accepter",
                'es' => "Presupuesto {$this->order->order_number} — revisar y aceptar",
                default => "Offerte {$this->order->order_number} — bekijk en accepteer",
            }
            : match ($this->mailLocale) {
                'en' => "Message about your request {$this->order->order_number}",
                'fr' => "Message concernant votre demande {$this->order->order_number}",
                'es' => "Mensaje sobre su solicitud {$this->order->order_number}",
                default => "Bericht over uw aanvraag {$this->order->order_number}",
            };

        $salesEmail = config('desnipperaar.notifications.sales_email');

        // Customer-facing mail always comes from DeSnipperaar <sales@>, never from the
        // individual admin who happens to have created the order.
        return new Envelope(
            subject: $subject,
            from: new Address($salesEmail, 'DeSnipperaar'),
            replyTo: [new Address($salesEmail, 'DeSnipperaar')],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: $this->mailLocale === 'nl' ? 'emails.quote-sent' : 'emails.'.$this->mailLocale.'.quote-sent',
            with: [
                'order'      => $this->order,
                'sender'     => $this->sender,
                'isOffer'    => ! is_null($this->order->quoted_amount_excl_btw),
                'acceptUrl'  => route('quote.show', $this->order->quote_token),
            ],
        );
    }
}
