<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Bevestiging dat een factuur is betaald.
 *
 * Bewust zonder bijlage: de klant heeft de factuur al gehad en dit is de
 * afronding, geen tweede exemplaar. Wie hem toch nog eens wil hebben, krijgt
 * hem via Resend op de factuurpagina.
 */
class PaymentReceived extends Mailable
{
    use Queueable, SerializesModels;

    public string $mailLocale;

    public function __construct(public Invoice $invoice, public ?User $sender = null)
    {
        $this->sender ??= $invoice->order?->senderUser();
        $orderLocale = $invoice->order?->locale;
        $this->mailLocale = in_array($orderLocale, ['nl', 'en', 'fr', 'es'], true) ? $orderLocale : 'nl';
    }

    public function envelope(): Envelope
    {
        $subject = match ($this->mailLocale) {
            'en' => "Payment received for invoice {$this->invoice->invoice_number} — DeSnipperaar",
            'fr' => "Paiement reçu pour la facture {$this->invoice->invoice_number} — DeSnipperaar",
            'es' => "Pago recibido de la factura {$this->invoice->invoice_number} — DeSnipperaar",
            default => "Betaling ontvangen voor factuur {$this->invoice->invoice_number} — DeSnipperaar",
        };

        $salesEmail = config('desnipperaar.notifications.sales_email');

        return new Envelope(
            subject: $subject,
            from: $this->sender
                ? new Address($this->sender->email, $this->sender->name)
                : null,
            replyTo: [new Address($salesEmail, 'DeSnipperaar')],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: $this->mailLocale === 'nl'
                ? 'emails.payment-received'
                : 'emails.'.$this->mailLocale.'.payment-received',
            with: ['invoice' => $this->invoice, 'sender' => $this->sender],
        );
    }
}
