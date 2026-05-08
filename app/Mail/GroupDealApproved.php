<?php

namespace App\Mail;

use App\Models\GroupDeal;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent to the organizer when admin approves their draft deal. */
class GroupDealApproved extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public GroupDeal $deal) {}

    public function envelope(): Envelope
    {
        $salesEmail = config('desnipperaar.notifications.sales_email');
        $adminEmail = config('desnipperaar.notifications.admin_email');

        $bcc = ($adminEmail
                && strcasecmp($adminEmail, $this->deal->organizerParticipant?->customer_email ?? '') !== 0)
            ? [new Address($adminEmail, 'DeSnipperaar')] : [];

        return new Envelope(
            subject: "Groepsdeal goedgekeurd · {$this->deal->city} ({$this->deal->pickup_date->toDateString()})",
            from: new Address($salesEmail, 'DeSnipperaar'),
            bcc: $bcc,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.group-deal-approved',
            with: ['deal' => $this->deal->fresh(['organizerParticipant'])],
        );
    }
}
