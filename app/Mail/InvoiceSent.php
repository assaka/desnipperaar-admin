<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceSent extends Mailable
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
            'en' => "Invoice {$this->invoice->invoice_number} — DeSnipperaar",
            'fr' => "Facture {$this->invoice->invoice_number} — DeSnipperaar",
            'es' => "Factura {$this->invoice->invoice_number} — DeSnipperaar",
            default => "Factuur {$this->invoice->invoice_number} — DeSnipperaar",
        };

        return new Envelope(
            subject: $subject,
            from: $this->sender
                ? new Address($this->sender->email, $this->sender->name)
                : null,
            replyTo: $this->sender
                ? [new Address($this->sender->email, $this->sender->name)]
                : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: $this->mailLocale === 'nl' ? 'emails.invoice-sent' : 'emails.'.$this->mailLocale.'.invoice-sent',
            with: ['invoice' => $this->invoice, 'sender' => $this->sender],
        );
    }

    public function attachments(): array
    {
        $view = match ($this->mailLocale) {
            'en' => 'invoices.pdf-en',
            'fr' => 'invoices.pdf-fr',
            'es' => 'invoices.pdf-es',
            default => 'invoices.pdf',
        };
        $name = match ($this->mailLocale) {
            'en' => "invoice-{$this->invoice->invoice_number}.pdf",
            'fr' => "facture-{$this->invoice->invoice_number}.pdf",
            'es' => "factura-{$this->invoice->invoice_number}.pdf",
            default => "factuur-{$this->invoice->invoice_number}.pdf",
        };
        $pdf = Pdf::loadView($view, ['invoice' => $this->invoice])->setPaper('a4');
        return [
            Attachment::fromData(fn () => $pdf->output(), $name)->withMime('application/pdf'),
        ];
    }
}
