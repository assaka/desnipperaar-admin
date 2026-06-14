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

class QuoteRequested extends Mailable
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
        $adminEmail = config('desnipperaar.notifications.admin_email');

        // BCC admin so the team inbox gets a copy. Skip only if the customer is the same address
        // (avoid duplicate). Sales@ is allowed even when it equals From, since Resend does not
        // deliver outbound mail back to the From-mailbox.
        $bcc = ($adminEmail
                && strcasecmp($adminEmail, $this->order->customer_email) !== 0)
            ? [new Address($adminEmail, 'DeSnipperaar')] : [];

        $subject = match ($this->mailLocale) {
            'en' => "Quote request {$this->order->order_number} received — DeSnipperaar",
            'fr' => "Demande de devis {$this->order->order_number} reçue — DeSnipperaar",
            'es' => "Solicitud de presupuesto {$this->order->order_number} recibida — DeSnipperaar",
            default => "Offerte-aanvraag {$this->order->order_number} ontvangen — DeSnipperaar",
        };

        return new Envelope(
            subject: $subject,
            from: new Address($salesEmail, 'DeSnipperaar'),
            replyTo: $this->sender
                ? [new Address($this->sender->email, $this->sender->name)]
                : [new Address($salesEmail, 'DeSnipperaar')],
            bcc: $bcc,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: $this->mailLocale === 'nl' ? 'emails.quote-requested' : 'emails.'.$this->mailLocale.'.quote-requested',
            with: ['order' => $this->order, 'sender' => $this->sender],
        );
    }
}
