<?php

namespace App\Console\Commands;

use App\Mail\DesnipperaarDagRateChange;
use App\Models\Subscriber;
use App\Support\DagDiscount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Tells every active subscriber that the SnipperDag percentage changes on the
 * date in config desnipperaar.dag, and that the current percentage holds until
 * then. Run once by hand. --to sends a single test copy to that address only.
 */
class SendDagRateNotice extends Command
{
    protected $signature = 'desnipperaar:dag-rate-notice
        {--to= : Send one test copy to this address instead of to the subscribers}
        {--lang=nl : Language of the test copy (nl, en, fr, es)}
        {--dry-run : List the recipients without sending}';

    protected $description = 'Notify SnipperDag subscribers of the upcoming discount change.';

    public function handle(): int
    {
        $new  = DagDiscount::next();
        $date = DagDiscount::switchDate();
        if ($new === null || ! $date) {
            $this->error('No planned change in config desnipperaar.dag (next_pct / next_from).');
            return self::FAILURE;
        }
        $old = DagDiscount::current();

        if ($to = $this->option('to')) {
            // Unsaved stand-in: a test copy must not create a subscriber row.
            $recipients = collect([new Subscriber([
                'email'             => $to,
                'lang'              => $this->option('lang'),
                'unsubscribe_token' => 'test-'.Str::random(16),
            ])]);
        } else {
            $recipients = Subscriber::active()->whereNotNull('unsubscribe_token')->get();
        }

        $this->info("{$recipients->count()} recipient(s): {$old}% -> {$new}% from {$date->toDateString()}.");
        if ($this->option('dry-run')) {
            foreach ($recipients as $sub) {
                $this->line("  {$sub->email} ({$sub->lang})");
            }
            return self::SUCCESS;
        }

        // Sent synchronously, like the weekly announcement (no live queue worker).
        $sent = 0;
        foreach ($recipients as $sub) {
            try {
                Mail::to($sub->email)->send(new DesnipperaarDagRateChange($sub, $old, $new));
                $sent++;
            } catch (\Throwable $e) {
                report($e);
                $this->error("  failed for {$sub->email}: {$e->getMessage()}");
            }
        }

        $this->info("Sent {$sent} notice(s).");
        return self::SUCCESS;
    }
}
