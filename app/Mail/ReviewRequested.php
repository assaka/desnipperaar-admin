<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * De vraag om een Google-review, na afloop van de klus.
 *
 * Bewust een persoonlijk berichtje en geen nieuwsbrief: geen orderregels, geen
 * bedragen, geen knoppenbalk. Hij komt van de persoon die de order deed en is
 * ondertekend met diens voornaam, want een gunst vraag je op je eigen naam.
 * Daarom staat de link ook uitgeschreven onder de tekst en niet verstopt in een
 * grote gele knop.
 *
 * Sturen doen wij met de hand vanaf de orderpagina, zodat wij er zelf naar
 * kijken of de klus goed ging voordat wij erom vragen.
 */
class ReviewRequested extends Mailable
{
    use Queueable, SerializesModels;

    public string $mailLocale;

    public function __construct(public Order $order, public ?User $sender = null)
    {
        $this->sender ??= $order->senderUser();
        $this->mailLocale = in_array($order->locale, ['nl', 'en', 'fr', 'es'], true) ? $order->locale : 'nl';
    }

    public function envelope(): Envelope
    {
        $subject = match ($this->mailLocale) {
            'en' => 'Would you leave us a review?',
            'fr' => 'Voulez-vous nous laisser un avis ?',
            'es' => '¿Nos deja una reseña?',
            default => 'Zou je een review willen achterlaten?',
        };

        return new Envelope(
            subject: $subject,
            from: $this->sender
                ? new Address($this->sender->email, $this->sender->name)
                : null,
            replyTo: [new Address(config('desnipperaar.notifications.sales_email'), 'DeSnipperaar')],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: $this->mailLocale === 'nl' ? 'emails.review-request' : 'emails.'.$this->mailLocale.'.review-request',
            with: [
                'order'     => $this->order,
                'sender'    => $this->sender,
                'reviewUrl' => (string) config('desnipperaar.review.url'),
            ],
        );
    }
}
