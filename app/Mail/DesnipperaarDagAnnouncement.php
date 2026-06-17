<?php

namespace App\Mail;

use App\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DesnipperaarDagAnnouncement extends Mailable
{
    use Queueable, SerializesModels;

    public string $mailLocale;

    public function __construct(
        public Subscriber $subscriber,
        public string $code,
        public int $pct,
    ) {
        $this->mailLocale = in_array($subscriber->lang, ['nl', 'en', 'fr', 'es'], true)
            ? $subscriber->lang : 'nl';
    }

    public function envelope(): Envelope
    {
        $salesEmail = config('desnipperaar.notifications.sales_email');

        $subject = match ($this->mailLocale) {
            'en' => "It's SnipperDag. {$this->pct}% off, today only.",
            'fr' => "C'est le SnipperDag. {$this->pct}% aujourd'hui seulement.",
            'es' => "Es el SnipperDag. {$this->pct}% solo hoy.",
            default => "Het is SnipperDag. Vandaag {$this->pct}% korting.",
        };

        return new Envelope(
            subject: $subject,
            from: new Address($salesEmail, 'DeSnipperaar'),
        );
    }

    public function content(): Content
    {
        $base = rtrim(config('desnipperaar.public_url'), '/');

        return new Content(
            view: $this->mailLocale === 'nl' ? 'emails.dag-announce' : 'emails.'.$this->mailLocale.'.dag-announce',
            with: [
                'code'           => $this->code,
                'pct'            => $this->pct,
                'orderUrl'       => $base . '/order?coupon=' . urlencode($this->code),
                'unsubscribeUrl' => $this->subscriber->unsubscribeUrl(),
            ],
        );
    }
}
