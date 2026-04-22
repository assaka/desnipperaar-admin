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
        $adminEmail = config('desnipperaar.notifications.admin_email');
        // BCC instead of CC — when from == admin the recipient's mail client would otherwise
        // suppress a CC-of-self; BCC to the same address is still delivered as a regular inbox item.
        $bcc = ($adminEmail && strcasecmp($adminEmail, $this->order->customer_email) !== 0)
            ? [new Address($adminEmail, 'DeSnipperaar')]
            : [];

        return new Envelope(
            subject: "Orderbevestiging {$this->order->order_number} — DeSnipperaar",
            from: $this->sender
                ? new Address($this->sender->email, $this->sender->name)
                : null,
            replyTo: $this->sender
                ? [new Address($this->sender->email, $this->sender->name)]
                : [],
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

        $mediaSubtotal = array_sum(array_column($mediaLines, 'subtotal'));
        $subtotal      = $quote['subtotal'] + $mediaSubtotal;
        $vat           = round($subtotal * 0.21, 2);
        $total         = round($subtotal + $vat, 2);

        return new Content(
            view: 'emails.order-created',
            with: [
                'order'       => $this->order,
                'sender'      => $this->sender,
                'quote'       => $quote,
                'mediaLines'  => $mediaLines,
                'subtotal'    => $subtotal,
                'vat'         => $vat,
                'total'       => $total,
            ],
        );
    }
}
