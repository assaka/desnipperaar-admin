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

class OrderCreated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public ?User $sender = null) {}

    public function envelope(): Envelope
    {
        $envelope = new Envelope(
            subject: "Bevestiging offerte {$this->order->order_number} — DeSnipperaar",
            from: $this->sender
                ? new Address($this->sender->email, $this->sender->name)
                : null,
            replyTo: $this->sender
                ? [new Address($this->sender->email, $this->sender->name)]
                : [],
        );
        return $envelope;
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-created',
            with: ['order' => $this->order, 'sender' => $this->sender],
        );
    }
}
