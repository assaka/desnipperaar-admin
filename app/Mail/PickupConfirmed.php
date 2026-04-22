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

class PickupConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public ?User $sender = null)
    {
        $this->sender ??= $order->senderUser();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Ophaalmoment bevestigd — {$this->order->order_number}",
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
            view: 'emails.pickup-confirmed',
            with: ['order' => $this->order, 'sender' => $this->sender],
        );
    }
}
