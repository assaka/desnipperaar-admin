<?php

namespace App\Mail;

use App\Models\Bon;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BonSigned extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Bon $bon, public ?User $sender = null)
    {
        $this->sender ??= $bon->order?->senderUser();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Getekende ophaalbon {$this->bon->bon_number} — DeSnipperaar",
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
            view: 'emails.bon-signed',
            with: ['bon' => $this->bon, 'sender' => $this->sender],
        );
    }

    public function attachments(): array
    {
        $pdf = Pdf::loadView('bons.pdf-dompdf', ['bon' => $this->bon])->setPaper('a4');

        return [
            Attachment::fromData(fn () => $pdf->output(), "ophaalbon-{$this->bon->bon_number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
