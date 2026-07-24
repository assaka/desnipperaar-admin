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

class QuoteValidityUpdated extends Mailable
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
        // Immutable quote reference (O-…) so a reply always carries a token that
        // still resolves after acceptance rewrites order_number to B-….
        $ref = $this->order->quote_reference ?? $this->order->order_number;

        $subject = match ($this->mailLocale) {
            'en' => "Quote {$ref} — new validity date",
            'fr' => "Devis {$ref} — nouvelle date de validité",
            'es' => "Presupuesto {$ref} — nueva fecha de validez",
            default => "Offerte {$ref} — nieuwe geldigheidsdatum",
        };

        // Opaque reply reference so replies link back to this order's history.
        $subject .= ' '.$this->order->replyTag();

        $salesEmail = config('desnipperaar.notifications.sales_email');

        return new Envelope(
            subject: $subject,
            from: new Address($salesEmail, 'DeSnipperaar'),
            replyTo: [new Address($salesEmail, 'DeSnipperaar')],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: $this->mailLocale === 'nl' ? 'emails.quote-validity-updated' : 'emails.'.$this->mailLocale.'.quote-validity-updated',
            with: [
                'order'     => $this->order,
                'sender'    => $this->sender,
                'acceptUrl' => rtrim(config('desnipperaar.public_url'), '/').'/offerte/'.$this->order->quote_token,
            ],
        );
    }
}
