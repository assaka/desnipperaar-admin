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

/** Confirmation to the participant who just edited their own data via the manage page. */
class GroupDealParticipantSelfUpdated extends Mailable
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
        $role = $isOrganizer ? 'organisator' : 'deelnemer';

        return new Envelope(
            subject: "Je wijziging is opgeslagen ({$role}) · {$this->deal->city} ({$this->deal->pickup_date->toDateString()})",
            from: new Address($salesEmail, 'DeSnipperaar'),
            to: [new Address($this->participant->customer_email, $this->participant->customer_name)],
            bcc: $bcc,
        );
    }

    public function content(): Content
    {
        $deal = $this->deal->fresh()->loadCount('participants');
        return new Content(
            view: 'emails.group-deal-participant-self-updated',
            with: [
                'deal'        => $deal,
                'participant' => $this->participant,
                'snapshot'    => $this->participant->price_snapshot,
            ],
        );
    }
}
