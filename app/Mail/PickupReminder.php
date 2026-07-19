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
 * Herinnering de dag voor een ophaling.
 *
 * Zonder deze mail hoorde een abonnementsklant helemaal niets: de planner maakt
 * de ophalingen stil aan, dus er stond ineens een bus voor de deur. Vooral als
 * een ophaaldag door een feestdag is verschoven moet de klant dat weten, want
 * dan klopt de vaste dag die hij gewend is niet.
 *
 * De locale komt van de ophaalorder zelf; die is bij aanmaken overgenomen van
 * het abonnement.
 */
class PickupReminder extends Mailable
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
        $salesEmail = config('desnipperaar.notifications.sales_email');
        $date = $this->order->pickup_date?->format('d-m-Y');

        $subject = match ($this->mailLocale) {
            'en' => "Reminder: we collect on {$date} — DeSnipperaar",
            'fr' => "Rappel : enlèvement le {$date} — DeSnipperaar",
            'es' => "Recordatorio: recogida el {$date} — DeSnipperaar",
            default => "Herinnering: wij halen op {$date} op — DeSnipperaar",
        };

        $subject .= ' '.$this->order->replyTag();

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
        $sub = $this->order->subscription;

        // Verschoven ten opzichte van het vaste ritme? Dan moet de mail dat
        // uitleggen, anders lijkt het een fout van ons.
        $shifted = $this->order->subscription_scheduled_for
            && $this->order->pickup_date
            && ! $this->order->subscription_scheduled_for->equalTo($this->order->pickup_date);

        return new Content(
            view: $this->mailLocale === 'nl' ? 'emails.pickup-reminder' : 'emails.'.$this->mailLocale.'.pickup-reminder',
            with: [
                'order'        => $this->order,
                'sender'       => $this->sender,
                'subscription' => $sub,
                'shifted'      => $shifted,
            ],
        );
    }
}
