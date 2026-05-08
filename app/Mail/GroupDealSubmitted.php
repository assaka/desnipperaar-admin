<?php

namespace App\Mail;

use App\Models\GroupDeal;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Internal notification to admin when a customer self-serves a draft group deal. */
class GroupDealSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public GroupDeal $deal) {}

    public function envelope(): Envelope
    {
        $salesEmail = config('desnipperaar.notifications.sales_email');

        return new Envelope(
            subject: "Nieuwe groepsdeal-aanvraag · {$this->deal->city} ({$this->deal->pickup_date->toDateString()})",
            from: new Address($salesEmail, 'DeSnipperaar'),
            to: [new Address($salesEmail, 'DeSnipperaar')],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.group-deal-submitted',
            with: ['deal' => $this->deal->fresh(['organizerParticipant'])],
        );
    }
}
