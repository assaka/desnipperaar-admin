<?php

namespace App\Mail;

use App\Models\GroupDeal;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Friendly receipt to the organizer right after they submit a draft.
 * Companion to GroupDealSubmitted, which goes to admin for moderation.
 */
class GroupDealReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public GroupDeal $deal) {}

    public function envelope(): Envelope
    {
        $salesEmail = config('desnipperaar.notifications.sales_email');

        return new Envelope(
            subject: "Je groepsdeal-voorstel is ontvangen — {$this->deal->city} ({$this->deal->pickup_date->toDateString()})",
            from: new Address($salesEmail, 'DeSnipperaar'),
            replyTo: [new Address($salesEmail, 'DeSnipperaar')],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.group-deal-received',
            with: ['deal' => $this->deal->fresh(['organizerParticipant'])],
        );
    }
}
