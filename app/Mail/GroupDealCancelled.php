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
 * Bulk-sent to all non-trashed participants when a deal is cancelled (organizer
 * stepped down with no remaining joiners, or admin cancelled the deal).
 * Caller supplies the list of recipients via `to()`.
 */
class GroupDealCancelled extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public GroupDeal $deal) {}

    public function envelope(): Envelope
    {
        $salesEmail = config('desnipperaar.notifications.sales_email');
        $adminEmail = config('desnipperaar.notifications.admin_email');

        $recipients = $this->deal->participants()->withTrashed()->get()
            ->map(fn ($p) => new Address($p->customer_email, $p->customer_name))
            ->all();

        $bcc = ($adminEmail) ? [new Address($adminEmail, 'DeSnipperaar')] : [];

        return new Envelope(
            subject: "Groepsdeal afgelast — {$this->deal->city} ({$this->deal->pickup_date->toDateString()})",
            from: new Address($salesEmail, 'DeSnipperaar'),
            to: $recipients,
            bcc: $bcc,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.group-deal-cancelled',
            with: ['deal' => $this->deal->fresh()],
        );
    }
}
