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
 * Bevestiging dat een creditfactuur is uitbetaald.
 *
 * De tegenhanger van PaymentReceived, maar dan geld de andere kant op. Die mail
 * bevestigt een ontvangst en kon hier dus niet hergebruikt worden.
 *
 * Ook hier geen bijlage. De creditfactuur heeft de klant al gehad toen wij hem
 * verstuurden; dit is het bericht dat het bedrag echt onderweg is.
 */
class RefundPaid extends Mailable
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
            'en' => "Refund for credit note {$this->invoice->invoice_number} — DeSnipperaar",
            'fr' => "Remboursement de l'avoir {$this->invoice->invoice_number} — DeSnipperaar",
            'es' => "Reembolso de la factura de abono {$this->invoice->invoice_number} — DeSnipperaar",
            default => "Terugbetaling van creditfactuur {$this->invoice->invoice_number} — DeSnipperaar",
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
                ? 'emails.refund-paid'
                : 'emails.'.$this->mailLocale.'.refund-paid',
            with: ['invoice' => $this->invoice, 'sender' => $this->sender],
        );
    }
}
