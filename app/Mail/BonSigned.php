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
        $this->mailLocale = in_array($orderLocale, ['nl', 'en', 'fr', 'es'], true) ? $orderLocale : 'nl';
    }

    public function envelope(): Envelope
    {
        // Onderwerp per soort rit: bezorging en retour zijn geen ophaling.
        $kind = match (true) {
            $this->bon->mode === 'bezorging' => [
                'nl' => 'Getekende bezorgbon', 'en' => 'Signed delivery receipt',
                'fr' => 'Bon de livraison signé', 'es' => 'Albarán de entrega firmado',
            ],
            $this->bon->mode === 'retour' => [
                'nl' => 'Getekende retourbon', 'en' => 'Signed return receipt',
                'fr' => 'Bon de retour signé', 'es' => 'Albarán de retorno firmado',
            ],
            default => [
                'nl' => 'Getekende ophaalbon', 'en' => 'Signed pickup receipt',
                'fr' => "Bon d'enlèvement signé", 'es' => 'Albarán de recogida firmado',
            ],
        };
        $subject = ($kind[$this->mailLocale] ?? $kind['nl'])." {$this->bon->bon_number} — DeSnipperaar";

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
            view: $this->mailLocale === 'nl' ? 'emails.bon-signed' : 'emails.'.$this->mailLocale.'.bon-signed',
            with: ['bon' => $this->bon, 'sender' => $this->sender],
        );
    }

    public function attachments(): array
    {
        $view = match ($this->mailLocale) {
            'en' => 'bons.pdf-dompdf-en',
            'fr' => 'bons.pdf-dompdf-fr',
            'es' => 'bons.pdf-dompdf-es',
            default => 'bons.pdf-dompdf',
        };
        $name = match ($this->mailLocale) {
            'en' => "pickup-receipt-{$this->bon->bon_number}.pdf",
            'fr' => "bon-enlevement-{$this->bon->bon_number}.pdf",
            'es' => "albaran-recogida-{$this->bon->bon_number}.pdf",
            default => "ophaalbon-{$this->bon->bon_number}.pdf",
        };
        $pdf = Pdf::loadView($view, ['bon' => $this->bon])->setPaper('a4');

        return [
            Attachment::fromData(fn () => $pdf->output(), $name)->withMime('application/pdf'),
        ];
    }
}
