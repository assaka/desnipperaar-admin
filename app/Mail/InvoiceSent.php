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

    public function __construct(public Invoice $invoice, public ?User $sender = null)
    {
        $this->sender ??= $invoice->order?->senderUser();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Factuur {$this->invoice->invoice_number} — DeSnipperaar",
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
            view: 'emails.invoice-sent',
            with: ['invoice' => $this->invoice, 'sender' => $this->sender],
        );
    }

    public function attachments(): array
    {
        $pdf = Pdf::loadView('invoices.pdf', ['invoice' => $this->invoice])->setPaper('a4');
        return [
            Attachment::fromData(fn () => $pdf->output(), "factuur-{$this->invoice->invoice_number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
