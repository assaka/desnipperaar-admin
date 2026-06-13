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

class QuoteSent extends Mailable
{
    use Queueable, SerializesModels;

    public string $mailLocale;

    public function __construct(public Order $order, public ?User $sender = null)
    {
        $this->sender ??= $order->senderUser();
        $this->mailLocale = in_array($order->locale, ['nl', 'en'], true) ? $order->locale : 'nl';
    }

    public function envelope(): Envelope
    {
        $subject = $this->mailLocale === 'en'
            ? "Quote {$this->order->order_number} — review and accept"
            : "Offerte {$this->order->order_number} — bekijk en accepteer";

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
            view: $this->mailLocale === 'en' ? 'emails.en.quote-sent' : 'emails.quote-sent',
            with: [
                'order'      => $this->order,
                'sender'     => $this->sender,
                'acceptUrl'  => route('quote.show', $this->order->quote_token),
            ],
        );
    }
}
