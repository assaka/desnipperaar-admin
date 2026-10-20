<?php

namespace App\Mail;

use App\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

/**
 * One-off notice to subscribers that the SnipperDag percentage changes on a
 * set date, and that the old percentage still applies until then.
 */
class DesnipperaarDagRateChange extends Mailable
{
    use Queueable, SerializesModels;

    public string $mailLocale;

    public function __construct(
        public Subscriber $subscriber,
        public int $oldPct,
        public int $newPct,
    ) {
        $this->mailLocale = in_array($subscriber->lang, ['nl', 'en', 'fr', 'es'], true)
            ? $subscriber->lang : 'nl';
    }

    public function envelope(): Envelope
    {
        $salesEmail = config('desnipperaar.notifications.sales_email');

        $subject = match ($this->mailLocale) {
            'en' => "Last chance for {$this->oldPct}% off on the DestructionDay",
            'fr' => "Dernière chance pour {$this->oldPct}% au Jour de Destruction",
            'es' => "Última oportunidad de {$this->oldPct}% en el Día de Destrucción",
            default => "Nog één keer {$this->oldPct}% korting op de SnipperDag",
        };

        return new Envelope(
            subject: $subject,
            from: new Address($salesEmail, 'DeSnipperaar'),
        );
    }

    /** RFC 8058 one-click unsubscribe, same as the other SnipperDag mails. */
    public function headers(): Headers
    {
        $url = $this->subscriber->unsubscribeUrl();

        return new Headers(text: [
            'List-Unsubscribe'      => '<'.$url.'>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ]);
    }

    public function content(): Content
    {
        return new Content(
            view: $this->mailLocale === 'nl' ? 'emails.dag-rate-change' : 'emails.'.$this->mailLocale.'.dag-rate-change',
            with: [
                'oldPct'         => $this->oldPct,
                'newPct'         => $this->newPct,
                'unsubscribeUrl' => $this->subscriber->unsubscribeUrl(),
            ],
        );
    }
}
