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
 * Zelfde jas als de rest van onze mail: kop, knop, footer. De toon eronder is
 * wel persoonlijk, want een gunst vraag je op je eigen naam. Daarom komt hij van
 * de persoon die de order deed en is hij ondertekend met diens voornaam.
 *
 * Als enige klantmail staat deze alleen in het Nederlands, ook bij een order in
 * een andere taal. Wat achter de knop zit is een Nederlands bedrijfsprofiel met
 * een Nederlands schrijfscherm, dus een Spaanse aanhef boven een Nederlandse
 * pagina wekt een verwachting die Google niet waarmaakt.
 *
 * De link staat ook uitgeschreven onder de knop. Een review die stukloopt op een
 * knop die niet rendeert is een review die je niet krijgt.
 *
 * Sturen doen wij met de hand vanaf de orderpagina, zodat wij er zelf naar
 * kijken of de klus goed ging voordat wij erom vragen. Wie liever appt gebruikt
 * daar het WhatsApp-sjabloon "Review vragen", zie App\Support\WhatsApp.
 */
class ReviewRequested extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public ?User $sender = null)
    {
        $this->sender ??= $order->senderUser();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Zou je een review willen achterlaten?',
            from: $this->sender
                ? new Address($this->sender->email, $this->sender->name)
                : null,
            replyTo: [new Address(config('desnipperaar.notifications.sales_email'), 'DeSnipperaar')],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.review-request',
            with: [
                'order'     => $this->order,
                'sender'    => $this->sender,
                'reviewUrl' => (string) config('desnipperaar.review.url'),
            ],
        );
    }
}
