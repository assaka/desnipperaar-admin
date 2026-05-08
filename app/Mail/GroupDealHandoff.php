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

/** Sent when the organizer cancels and the role passes to the next-oldest joiner. */
class GroupDealHandoff extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public GroupDeal $deal,
        public GroupDealParticipant $newOrganizer,
        public ?GroupDealParticipant $previousOrganizer = null,
    ) {}

    public function envelope(): Envelope
    {
        $salesEmail = config('desnipperaar.notifications.sales_email');
        $adminEmail = config('desnipperaar.notifications.admin_email');

        $bcc = $adminEmail ? [new Address($adminEmail, 'DeSnipperaar')] : [];

        return new Envelope(
            subject: "Groepdeal — je bent nu de organisator ({$this->deal->city} {$this->deal->pickup_date->toDateString()})",
            from: new Address($salesEmail, 'DeSnipperaar'),
            to: [new Address($this->newOrganizer->customer_email, $this->newOrganizer->customer_name)],
            bcc: $bcc,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.group-deal-handoff',
            with: [
                'deal'              => $this->deal,
                'newOrganizer'      => $this->newOrganizer,
                'previousOrganizer' => $this->previousOrganizer,
            ],
        );
    }
}
