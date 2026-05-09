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
 * Sent to the organizer once the deal is closed (orders materialized) so they
 * know what bonus is coming and can reply with their IBAN. We deliberately
 * don't store the IBAN — admin reads it from the reply and triggers the
 * bank transfer manually.
 */
class GroupDealOrganizerBonus extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public GroupDeal $deal,
        public GroupDealParticipant $organizer,
        public float $bonusAmount,
        public int $commissionPct,
    ) {}

    public function envelope(): Envelope
    {
        $salesEmail = config('desnipperaar.notifications.sales_email');
        $adminEmail = config('desnipperaar.notifications.admin_email');

        $bcc = ($adminEmail
                && strcasecmp($adminEmail, $this->organizer->customer_email) !== 0)
            ? [new Address($adminEmail, 'DeSnipperaar')] : [];

        return new Envelope(
            subject: "Je organisator-bonus klaar voor uitbetaling · {$this->deal->city} ({$this->deal->pickup_date->toDateString()})",
            from: new Address($salesEmail, 'DeSnipperaar'),
            to: [new Address($this->organizer->customer_email, $this->organizer->customer_name)],
            replyTo: [new Address($salesEmail, 'DeSnipperaar Sales')],
            bcc: $bcc,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.group-deal-organizer-bonus',
            with: [
                'deal'           => $this->deal,
                'organizer'      => $this->organizer,
                'bonusAmount'    => $this->bonusAmount,
                'commissionPct'  => $this->commissionPct,
            ],
        );
    }
}
