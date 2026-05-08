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
 * Notifies the organizer that a non-organizer deelnemer cancelled their join.
 * Carries the volume the leaving deelnemer would have brought, plus updated stats.
 */
class GroupDealParticipantLeft extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public GroupDeal $deal,
        public GroupDealParticipant $leftParticipant,
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
            subject: "Deelnemer afgemeld in je groepsdeal · {$this->deal->city} ({$this->deal->pickup_date->toDateString()})",
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

        $first = preg_split('/\s+/', trim($this->leftParticipant->customer_name))[0]
            ?? $this->leftParticipant->customer_name;
        $postcodePrefix = substr(
            preg_replace('/\s+/', '', (string) $this->leftParticipant->customer_postcode),
            0, 4
        );

        return new Content(
            view: 'emails.group-deal-participant-left',
            with: [
                'deal'              => $deal,
                'organizer'         => $this->organizer,
                'firstName'         => $first,
                'postcodePrefix'    => $postcodePrefix,
                'leftBoxCount'      => (int) $this->leftParticipant->box_count,
                'leftContainerCount'=> (int) $this->leftParticipant->container_count,
                'participantCount'  => $deal->participants_count,
                'filledBoxes'       => (int) ($filled->boxes ?? 0),
                'filledContainers'  => (int) ($filled->containers ?? 0),
            ],
        );
    }
}
