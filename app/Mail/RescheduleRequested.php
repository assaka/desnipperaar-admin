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

class RescheduleRequested extends Mailable
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
            'en' => "Reschedule request {$this->order->order_number} received — DeSnipperaar",
            'fr' => "Demande de modification {$this->order->order_number} reçue — DeSnipperaar",
            'es' => "Solicitud de cambio {$this->order->order_number} recibida — DeSnipperaar",
            default => "Wijzigingsverzoek {$this->order->order_number} ontvangen — DeSnipperaar",
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
            view: $this->mailLocale === 'nl' ? 'emails.reschedule-requested' : 'emails.'.$this->mailLocale.'.reschedule-requested',
            with: ['order' => $this->order, 'sender' => $this->sender],
        );
    }
}
