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

    public string $mailLocale;

    public function __construct(public Bon $bon, public ?User $sender = null)
    {
        $this->sender ??= $bon->order?->senderUser();
        $orderLocale = $bon->order?->locale;
        $this->mailLocale = in_array($orderLocale, ['nl', 'en'], true) ? $orderLocale : 'nl';
    }

    public function envelope(): Envelope
    {
        $subject = $this->mailLocale === 'en'
            ? "Signed pickup receipt {$this->bon->bon_number} — DeSnipperaar"
            : "Getekende ophaalbon {$this->bon->bon_number} — DeSnipperaar";

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
            view: $this->mailLocale === 'en' ? 'emails.en.bon-signed' : 'emails.bon-signed',
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
