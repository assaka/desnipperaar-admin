<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Interne melding: de klant heeft zijn ophaalmoment zelf gekozen op de
 * planpagina. Geen verzoek zoals bij een herplanning, want het moment kwam uit
 * onze eigen lijst met beschikbare momenten en staat dus al vast. Wat er nog
 * moet gebeuren is een chauffeur toewijzen.
 */
class PickupPlannedByCustomer extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Klant plande ophaling {$this->order->order_number} — {$this->order->customer_name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.pickup-planned-by-customer',
            with: ['order' => $this->order],
        );
    }
}
