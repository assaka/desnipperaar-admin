<?php

namespace App\Mail;

use App\Models\Bon;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Herinnering de dag voor een ophaling.
 *
 * Een ophaling is een bon op de abonnementsorder, geen eigen order meer. De mail
 * hangt daarom aan de bon: de datum staat op de bon (planned_for), de klant op de
 * order eronder. Zonder deze mail hoort de klant niets en staat er ineens een bus
 * voor de deur.
 */
class PickupReminder extends Mailable
{
    use Queueable, SerializesModels;

    public string $mailLocale;

    public function __construct(public Bon $visit, public ?User $sender = null)
    {
        $order = $visit->order;
        $this->sender ??= $order->senderUser();
        $this->mailLocale = in_array($order->locale, ['nl', 'en', 'fr', 'es'], true) ? $order->locale : 'nl';
    }

    public function envelope(): Envelope
    {
        $salesEmail = config('desnipperaar.notifications.sales_email');
        $date = $this->visit->planned_for?->format('d-m-Y');

        $subject = match ($this->mailLocale) {
            'en' => "Reminder: we collect on {$date} — DeSnipperaar",
            'fr' => "Rappel : enlèvement le {$date} — DeSnipperaar",
            'es' => "Recordatorio: recogida el {$date} — DeSnipperaar",
            default => "Herinnering: wij halen op {$date} op — DeSnipperaar",
        };

        $subject .= ' '.$this->visit->order->replyTag();

        return new Envelope(
            subject: $subject,
            from: new Address($salesEmail, 'DeSnipperaar'),
            replyTo: $this->sender
                ? [new Address($this->sender->email, $this->sender->name)]
                : [new Address($salesEmail, 'DeSnipperaar')],
        );
    }

    public function content(): Content
    {
        // Verschoven ten opzichte van het vaste ritme? Dan moet de mail dat
        // uitleggen, anders lijkt het een fout van ons.
        $shifted = $this->visit->scheduled_for
            && $this->visit->planned_for
            && ! $this->visit->scheduled_for->equalTo($this->visit->planned_for);

        return new Content(
            view: $this->mailLocale === 'nl' ? 'emails.pickup-reminder' : 'emails.'.$this->mailLocale.'.pickup-reminder',
            with: [
                'order'   => $this->visit->order,
                'visit'   => $this->visit,
                'sender'  => $this->sender,
                'shifted' => $shifted,
            ],
        );
    }
}
