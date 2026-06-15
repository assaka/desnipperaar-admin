<?php

namespace App\Mail;

use App\Models\Certificate;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Mail\Mailables\Attachment;
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
        $this->mailLocale = in_array($orderLocale, ['nl', 'en', 'fr', 'es'], true) ? $orderLocale : 'nl';
    }

    public function envelope(): Envelope
    {
        $subject = match ($this->mailLocale) {
            'en' => "Certificate of destruction {$this->certificate->certificate_number}",
            'fr' => "Certificat de destruction {$this->certificate->certificate_number}",
            'es' => "Certificado de destrucción {$this->certificate->certificate_number}",
            default => "Certificaat van vernietiging {$this->certificate->certificate_number}",
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
            view: $this->mailLocale === 'nl' ? 'emails.certificate-issued' : 'emails.'.$this->mailLocale.'.certificate-issued',
            with: ['certificate' => $this->certificate],
        );
    }

    public function attachments(): array
    {
        $this->certificate->loadMissing(['order.bons.seals', 'order.customer']);

        $name = match ($this->mailLocale) {
            'en' => "certificate-of-destruction-{$this->certificate->certificate_number}.pdf",
            'fr' => "certificat-de-destruction-{$this->certificate->certificate_number}.pdf",
            'es' => "certificado-de-destruccion-{$this->certificate->certificate_number}.pdf",
            default => "vernietigingscertificaat-{$this->certificate->certificate_number}.pdf",
        };

        $pdf = Pdf::loadView('certificates.pdf-dompdf', [
            'certificate' => $this->certificate,
            'locale'      => $this->mailLocale,
        ])->setPaper('a4');

        return [
            Attachment::fromData(fn () => $pdf->output(), $name)->withMime('application/pdf'),
        ];
    }
}
