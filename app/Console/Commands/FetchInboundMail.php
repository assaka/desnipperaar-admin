<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderMessage;
use Illuminate\Console\Command;

/**
 * Polls the sales IMAP inbox and logs client replies to order history.
 * No-ops safely until webklex/laravel-imap is installed and IMAP creds
 * are set in .env (IMAP_HOST / IMAP_USERNAME / IMAP_PASSWORD / ...).
 */
class FetchInboundMail extends Command
{
    protected $signature = 'mail:fetch-inbound {--limit=50}';

    protected $description = 'Fetch client replies from the sales inbox (IMAP) into order message history';

    public function handle(): int
    {
        if (! class_exists(\Webklex\IMAP\Facades\Client::class)) {
            $this->warn('webklex/laravel-imap not installed — skipping inbound fetch.');
            return self::SUCCESS;
        }
        if (! config('imap.accounts.default.password')) {
            $this->warn('IMAP not configured (no password) — skipping inbound fetch.');
            return self::SUCCESS;
        }

        $client = \Webklex\IMAP\Facades\Client::account('default');

        try {
            $client->connect();
        } catch (\Throwable $e) {
            $this->error('IMAP connect failed: ' . $e->getMessage());
            report($e);
            return self::FAILURE;
        }

        $folder   = $client->getFolderByName('INBOX');
        $messages = $folder->query()->unseen()->limit((int) $this->option('limit'))->get();

        $logged = 0;
        foreach ($messages as $message) {
            try {
                $from    = optional($message->getFrom()[0] ?? null)->mail;
                $to      = optional($message->getTo()[0] ?? null)->mail;
                $subject = (string) $message->getSubject();
                $uid     = (string) $message->getUid();
                $body    = (string) $message->getTextBody();

                $order = $this->matchOrder($subject, $body, $from);
                if (! $order) {
                    // Leave unread so a human still notices it; don't log an unmatched reply.
                    continue;
                }

                OrderMessage::firstOrCreate(
                    ['channel' => 'email', 'direction' => 'in', 'external_id' => $uid],
                    [
                        'order_id'    => $order->id,
                        'from_email'  => $from,
                        'to_email'    => $to,
                        'subject'     => $subject,
                        'body_text'   => $body,
                        'body_html'   => $message->getHTMLBody(),
                        'occurred_at' => $message->getDate() ? $message->getDate()->toDate() : now(),
                    ]
                );

                $message->setFlag('Seen');
                $logged++;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $this->info("Inbound fetch done. Logged {$logged} message(s).");
        return self::SUCCESS;
    }

    private function matchOrder(string $subject, ?string $body, ?string $fromEmail): ?Order
    {
        // Prefer an explicit reference in the reply. Match against quote_reference
        // (the immutable O- code that stays put after acceptance) as well as
        // order_number, which is rewritten from O- to B- once a quote is accepted.
        if (preg_match_all('/\b([A-Z]{1,3}-\d{4}-\d{3,})\b/', $subject . ' ' . ($body ?? ''), $m)) {
            foreach (array_unique($m[1]) as $ref) {
                $order = Order::where('quote_reference', $ref)
                    ->orWhere('order_number', $ref)
                    ->first();
                if ($order) {
                    return $order;
                }
            }
        }
        if ($fromEmail) {
            return Order::whereRaw('lower(customer_email) = ?', [strtolower($fromEmail)])
                ->latest('id')->first();
        }
        return null;
    }
}
