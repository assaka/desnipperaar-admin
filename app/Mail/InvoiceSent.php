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
        $this->mailLocale = in_array($orderLocale, ['nl', 'en'], true) ? $orderLocale : 'nl';
    }

    public function envelope(): Envelope
    {
        $subject = $this->mailLocale === 'en'
            ? "Invoice {$this->invoice->invoice_number} — DeSnipperaar"
            : "Factuur {$this->invoice->invoice_number} — DeSnipperaar";

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
            view: $this->mailLocale === 'en' ? 'emails.en.invoice-sent' : 'emails.invoice-sent',
            with: ['invoice' => $this->invoice, 'sender' => $this->sender],
        );
    }

    public function attachments(): array
    {
        $view = $this->mailLocale === 'en' ? 'invoices.pdf-en' : 'invoices.pdf';
        $name = $this->mailLocale === 'en'
            ? "invoice-{$this->invoice->invoice_number}.pdf"
            : "factuur-{$this->invoice->invoice_number}.pdf";
        $pdf = Pdf::loadView($view, ['invoice' => $this->invoice])->setPaper('a4');
        return [
            Attachment::fromData(fn () => $pdf->output(), $name)->withMime('application/pdf'),
        ];
    }
}
