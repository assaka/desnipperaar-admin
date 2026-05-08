<?php

namespace App\Mail;

use App\Models\GroupDeal;
use App\Models\GroupDealParticipant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent to a participant who just joined an open deal; admin BCC'd for visibility. */
class GroupDealJoined extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public GroupDeal $deal, public GroupDealParticipant $participant) {}

    public function envelope(): Envelope
    {
        $salesEmail = config('desnipperaar.notifications.sales_email');
        $adminEmail = config('desnipperaar.notifications.admin_email');

        $bcc = ($adminEmail
                && strcasecmp($adminEmail, $this->participant->customer_email) !== 0)
            ? [new Address($adminEmail, 'DeSnipperaar')] : [];

        $isOrganizer = $this->participant->id === $this->deal->organizer_participant_id;
        $subject = $isOrganizer
            ? "Welkom in de groepsdeal als organisator · {$this->deal->city} ({$this->deal->pickup_date->toDateString()})"
            : "Welkom in de groepsdeal · {$this->deal->city} ({$this->deal->pickup_date->toDateString()})";

        return new Envelope(
            subject: $subject,
            from: new Address($salesEmail, 'DeSnipperaar'),
            bcc: $bcc,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.group-deal-joined',
            with: [
                'deal'        => $this->deal->fresh()->loadCount('participants'),
                'participant' => $this->participant,
                'snapshot'    => $this->participant->price_snapshot,
            ],
        );
    }
}
