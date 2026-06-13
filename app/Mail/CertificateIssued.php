<?php

namespace App\Mail;

use App\Models\Certificate;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CertificateIssued extends Mailable
{
    use Queueable, SerializesModels;

    public string $mailLocale;

    public function __construct(public Certificate $certificate, public ?User $sender = null)
    {
        $this->sender ??= $certificate->order?->senderUser();
        $orderLocale = $certificate->order?->locale;
        $this->mailLocale = in_array($orderLocale, ['nl', 'en'], true) ? $orderLocale : 'nl';
    }

    public function envelope(): Envelope
    {
        $subject = $this->mailLocale === 'en'
            ? "Certificate of destruction {$this->certificate->certificate_number}"
            : "Certificaat van vernietiging {$this->certificate->certificate_number}";

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
            view: $this->mailLocale === 'en' ? 'emails.en.certificate-issued' : 'emails.certificate-issued',
            with: ['certificate' => $this->certificate],
        );
    }
}
