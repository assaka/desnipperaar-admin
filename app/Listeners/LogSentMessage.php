<?php

namespace App\Listeners;

use App\Models\Order;
use App\Models\OrderMessage;
use Illuminate\Mail\Events\MessageSent;
use Symfony\Component\Mime\Email;

/**
 * Logs every outbound email that carries an Order in its view data to the
 * order's message history. Internal notifications addressed to the sales
 * inbox (e.g. SalesAlert) are skipped so the thread stays customer-facing.
 */
class LogSentMessage
{
    public function handle(MessageSent $event): void
    {
        try {
            $order = $this->findOrder($event->data);
            if (! $order) {
                return;
            }

            $sym = $event->sent->getOriginalMessage();
            if (! $sym instanceof Email) {
                return;
            }

            $to   = $this->firstAddress($sym->getTo());
            $from = $this->firstAddress($sym->getFrom());

            // Skip internal notifications sent to our own sales inbox.
            $sales = config('desnipperaar.notifications.sales_email');
            if ($sales && $to && strcasecmp($to, $sales) === 0) {
                return;
            }

            $externalId = null;
            try {
                $externalId = $event->sent->getMessageId();
            } catch (\Throwable $e) {
                // some transports don't expose a message id
            }

            OrderMessage::create([
                'order_id'    => $order->id,
                'direction'   => 'out',
                'channel'     => 'email',
                'from_email'  => $from,
                'to_email'    => $to,
                'subject'     => $sym->getSubject(),
                'body_html'   => $sym->getHtmlBody(),
                'body_text'   => $sym->getTextBody(),
                'external_id' => $externalId,
                'occurred_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function findOrder(array $data): ?Order
    {
        foreach ($data as $value) {
            if ($value instanceof Order) {
                return $value;
            }
        }
        foreach ($data as $value) {
            if (is_object($value) && isset($value->order) && $value->order instanceof Order) {
                return $value->order;
            }
        }
        return null;
    }

    private function firstAddress(array $addresses): ?string
    {
        foreach ($addresses as $address) {
            return $address->getAddress();
        }
        return null;
    }
}
