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

        // Use the immutable quote reference (O-…) so the reply always carries a token
        // that still resolves after acceptance rewrites order_number to B-….
        $ref = $this->order->quote_reference ?? $this->order->order_number;

        $subject = $isOffer
            ? match ($this->mailLocale) {
                'en' => "Quote {$ref} — review and accept",
                'fr' => "Devis {$ref} — vérifier et accepter",
                'es' => "Presupuesto {$ref} — revisar y aceptar",
                default => "Offerte {$ref} — bekijk en accepteer",
            }
            : match ($this->mailLocale) {
                'en' => "Message about your request {$ref}",
                'fr' => "Message concernant votre demande {$ref}",
                'es' => "Mensaje sobre su solicitud {$ref}",
                default => "Bericht over uw aanvraag {$ref}",
            };

        // Opaque reply reference so replies link back to this order's history.
        $subject .= ' '.$this->order->replyTag();

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
                // Customer-facing quote lives on the public domain, not admin.*
                'acceptUrl'  => rtrim(config('desnipperaar.public_url'), '/').'/offerte/'.$this->order->quote_token,
            ],
        );
    }
}
