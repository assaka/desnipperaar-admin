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

        // Sales is on the visible CC line (not BCC) so the organizer sees they
        // can reply-all and DeSnipperaar will get the IBAN. Skip when sales@
        // happens to be the same address as the organizer (avoids self-CC).
        $cc = ($salesEmail
                && strcasecmp($salesEmail, $this->organizer->customer_email) !== 0)
            ? [new Address($salesEmail, 'DeSnipperaar Sales')] : [];

        return new Envelope(
            subject: "Je organisator-bonus klaar voor uitbetaling · {$this->deal->city} ({$this->deal->pickup_date->toDateString()})",
            from: new Address($salesEmail, 'DeSnipperaar'),
            to: [new Address($this->organizer->customer_email, $this->organizer->customer_name)],
            cc: $cc,
            replyTo: [new Address($salesEmail, 'DeSnipperaar Sales')],
        );
    }

    public function content(): Content
    {
        // Joiner roster (excludes the organizer + soft-deleted participants).
        // First name only per privacy rule we use elsewhere on the site.
        $deelnemers = $this->deal->participants()
            ->where('id', '!=', $this->organizer->id)
            ->orderBy('created_at')
            ->get(['customer_name', 'box_count', 'container_count', 'price_snapshot'])
            ->map(function ($p) {
                $first = preg_split('/\s+/', trim($p->customer_name))[0] ?? $p->customer_name;
                return [
                    'first_name'      => $first,
                    'box_count'       => (int) $p->box_count,
                    'container_count' => (int) $p->container_count,
                    'subtotal'        => (float) ($p->price_snapshot['subtotal'] ?? 0),
                ];
            })->all();

        return new Content(
            view: 'emails.group-deal-organizer-bonus',
            with: [
                'deal'           => $this->deal,
                'organizer'      => $this->organizer,
                'bonusAmount'    => $this->bonusAmount,
                'commissionPct'  => $this->commissionPct,
                'deelnemers'     => $deelnemers,
            ],
        );
    }
}
