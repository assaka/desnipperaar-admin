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

/**
 * Notifies the organizer that a new deelnemer joined their deal, with the
 * latest stats (deelnemers count, filled boxes/containers vs target).
 * Only fires for non-organizer joins.
 */
class GroupDealParticipantJoined extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public GroupDeal $deal,
        public GroupDealParticipant $newParticipant,
        public GroupDealParticipant $organizer,
    ) {}

    public function envelope(): Envelope
    {
        $salesEmail = config('desnipperaar.notifications.sales_email');
        $adminEmail = config('desnipperaar.notifications.admin_email');

        $bcc = ($adminEmail
                && strcasecmp($adminEmail, $this->organizer->customer_email) !== 0)
            ? [new Address($adminEmail, 'DeSnipperaar')] : [];

        return new Envelope(
            subject: "Nieuwe deelnemer in je groepsdeal · {$this->deal->city} ({$this->deal->pickup_date->toDateString()})",
            from: new Address($salesEmail, 'DeSnipperaar'),
            to: [new Address($this->organizer->customer_email, $this->organizer->customer_name)],
            bcc: $bcc,
        );
    }

    public function content(): Content
    {
        $deal = $this->deal->fresh()->loadCount('participants');
        $filled = $deal->participants()
            ->selectRaw('COALESCE(SUM(box_count), 0) AS boxes, COALESCE(SUM(container_count), 0) AS containers')
            ->first();

        $newFirst = preg_split('/\s+/', trim($this->newParticipant->customer_name))[0]
            ?? $this->newParticipant->customer_name;
        $newPostcodePrefix = substr(
            preg_replace('/\s+/', '', (string) $this->newParticipant->customer_postcode),
            0, 4
        );

        return new Content(
            view: 'emails.group-deal-participant-joined',
            with: [
                'deal'              => $deal,
                'organizer'         => $this->organizer,
                'newFirstName'      => $newFirst,
                'newPostcodePrefix' => $newPostcodePrefix,
                'newBoxCount'       => (int) $this->newParticipant->box_count,
                'newContainerCount' => (int) $this->newParticipant->container_count,
                'participantCount'  => $deal->participants_count,
                'filledBoxes'       => (int) ($filled->boxes ?? 0),
                'filledContainers'  => (int) ($filled->containers ?? 0),
            ],
        );
    }
}
