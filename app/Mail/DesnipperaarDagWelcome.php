<?php

namespace App\Mail;

use App\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent immediately when someone signs up for SnipperDag. Confirms the
 * subscription, sets expectations, and carries the AVG unsubscribe link.
 */
class DesnipperaarDagWelcome extends Mailable
{
    use Queueable, SerializesModels;

    public string $mailLocale;

    public function __construct(public Subscriber $subscriber, public int $pct = 35)
    {
        $this->mailLocale = in_array($subscriber->lang, ['nl', 'en', 'fr', 'es'], true)
            ? $subscriber->lang : 'nl';
    }

    public function envelope(): Envelope
    {
        $salesEmail = config('desnipperaar.notifications.sales_email');

        $subject = match ($this->mailLocale) {
            'en' => 'Welcome to the DestructionDay',
            'fr' => 'Bienvenue au JourDestruction',
            'es' => 'Bienvenido al DíaDestrucción',
            default => 'Welkom bij de SnipperDag',
        };

        return new Envelope(
            subject: $subject,
            from: new Address($salesEmail, 'DeSnipperaar'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: $this->mailLocale === 'nl' ? 'emails.dag-welcome' : 'emails.'.$this->mailLocale.'.dag-welcome',
            with: [
                'pct'            => $this->pct,
                'unsubscribeUrl' => $this->subscriber->unsubscribeUrl(),
            ],
        );
    }
}
