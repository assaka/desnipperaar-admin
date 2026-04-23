<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderCreated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public ?User $sender = null)
    {
        $this->sender ??= $order->senderUser();
    }

    public function envelope(): Envelope
    {
        $salesEmail = config('desnipperaar.notifications.sales_email');
        $adminEmail = config('desnipperaar.notifications.admin_email');

        // BCC admin for silent oversight, skip if same as recipient or same as from.
        $bcc = ($adminEmail
                && strcasecmp($adminEmail, $this->order->customer_email) !== 0
                && strcasecmp($adminEmail, $salesEmail) !== 0)
            ? [new Address($adminEmail, 'Hamid El Abassi')] : [];

        return new Envelope(
            subject: "Orderbevestiging {$this->order->order_number} — DeSnipperaar",
            from: new Address($salesEmail, 'DeSnipperaar'),
            replyTo: $this->sender
                ? [new Address($this->sender->email, $this->sender->name)]
                : [new Address($salesEmail, 'DeSnipperaar')],
            bcc: $bcc,
        );
    }

    public function content(): Content
    {
        $quote = \App\Support\Pricing::quote(
            (int) $this->order->box_count,
            (int) $this->order->container_count,
            (bool) $this->order->pilot,
            (bool) $this->order->first_box_free,
        );

        $mediaLines = [];
        $mediaPrices = ['hdd' => 9, 'ssd' => 15, 'usb' => 6, 'phone' => 12, 'laptop' => 19];
        $mediaLabels = ['hdd' => 'HDD', 'ssd' => 'SSD / NVMe', 'usb' => 'USB / SD', 'phone' => 'Telefoon / tablet', 'laptop' => 'Laptop'];
        foreach (($this->order->media_items ?? []) as $key => $qty) {
            $qty = (int) $qty;
            if ($qty <= 0 || !isset($mediaPrices[$key])) continue;
            $mediaLines[] = [
                'label'    => $mediaLabels[$key] ?? ucfirst($key),
                'qty'      => $qty,
                'unit'     => $mediaPrices[$key],
                'subtotal' => $mediaPrices[$key] * $qty,
            ];
        }

        $mediaSubtotal   = array_sum(array_column($mediaLines, 'subtotal'));
        $subtotal        = $quote['subtotal'] + $mediaSubtotal;
        $subtotalRegular = ($quote['subtotal_regular'] ?? $quote['subtotal']) + $mediaSubtotal;
        $discount        = round($subtotalRegular - $subtotal, 2);
        $vat             = round($subtotal * 0.21, 2);
        $total           = round($subtotal + $vat, 2);

        return new Content(
            view: 'emails.order-created',
            with: [
                'order'           => $this->order,
                'sender'          => $this->sender,
                'quote'           => $quote,
                'mediaLines'      => $mediaLines,
                'subtotal'        => $subtotal,
                'subtotalRegular' => $subtotalRegular,
                'discount'        => $discount,
                'vat'             => $vat,
                'total'           => $total,
            ],
        );
    }
}
