<?php

namespace App\Console\Commands;

use App\Mail\DesnipperaarDagAnnouncement;
use App\Models\Coupon;
use App\Models\DagAnnouncement;
use App\Models\Subscriber;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

/**
 * DeSnipperaar Dag: one random weekday (Mon–Fri) each week is the discount day.
 *
 * The chosen day is derived deterministically from the ISO week so a daily cron
 * always agrees on "is today the day" without storing a schedule, yet the day
 * differs from week to week. On that day the command mints a fresh single-use-day
 * coupon (random code prefixed "SnipperDag", 35%, expiring at midnight) and
 * e-mails every active subscriber in their own language. A fresh code per event
 * means a leaked code can't be reused on another day. Idempotent: the unique
 * dag_announcements row (which also stores that day's code) stops a double send
 * and a double mint.
 */
class SendDagAnnouncement extends Command
{
    protected $signature = 'desnipperaar:dag-announce
        {--force : Treat today as the Dag and resend, ignoring the weekday pick and the already-sent guard}
        {--dry-run : Report what would happen without creating the coupon or sending mail}';

    protected $description = 'Announce DeSnipperaar Dag to subscribers when today is this week\'s random discount day.';

    private const PREFIX = 'SnipperDag';
    private const PCT    = 35;

    public function handle(): int
    {
        $today  = now('Europe/Amsterdam');
        $chosen = $this->chosenWeekday($today);
        $isDay  = $today->dayOfWeekIso === $chosen || $this->option('force');

        $this->line("This week's DeSnipperaar Dag falls on " . Carbon::now('Europe/Amsterdam')
            ->startOfWeek()->addDays($chosen - 1)->locale('nl')->translatedFormat('l') . '.');

        if (! $isDay) {
            $this->deactivateExpired();
            $this->info('Today is not the Dag. Nothing to send.');
            return self::SUCCESS;
        }

        $already = DagAnnouncement::whereDate('announced_on', $today->toDateString())->exists();
        if ($already && ! $this->option('force')) {
            $this->info('Already announced today. Skipping.');
            return self::SUCCESS;
        }

        $recipients = Subscriber::active()->whereNotNull('unsubscribe_token')->get();
        $this->info("DeSnipperaar Dag is today. {$recipients->count()} active subscriber(s), " . self::PCT . '%.');

        if ($this->option('dry-run')) {
            $this->warn('Dry run: no coupon minted, no mail sent.');
            return self::SUCCESS;
        }

        // Mint a fresh single-day coupon, or reuse today's on a --force re-run.
        $record = DagAnnouncement::firstOrNew(['announced_on' => $today->toDateString()]);
        $coupon = $record->code ? Coupon::where('code', $record->code)->first() : null;

        if (! $coupon) {
            $record->code = $this->freshCode();
            $coupon = new Coupon(['code' => $record->code, 'type' => 'percentage', 'value' => self::PCT]);
        }
        // Valid for this one day only (created today, expires tonight).
        $coupon->fill([
            'is_active'   => true,
            'expires_at'  => $today->copy()->endOfDay(),
            'description' => 'DeSnipperaar Dag ' . $today->toDateString(),
        ])->save();

        $pct = (int) round((float) $coupon->value);
        $this->info("Code for today: {$coupon->code} ({$pct}%, expires {$coupon->expires_at->format('Y-m-d H:i')}).");

        // Sent synchronously (like OrderController): this host has no live queue
        // worker for the app, so a queued mail would never be delivered.
        $sent = 0;
        foreach ($recipients as $sub) {
            try {
                Mail::to($sub->email)->send(new DesnipperaarDagAnnouncement($sub, $coupon->code, $pct));
                $sent++;
            } catch (\Throwable $e) {
                report($e);
                $this->error("  failed for {$sub->email}: {$e->getMessage()}");
            }
        }

        $record->recipients = $sent;
        $record->save();

        $this->info("Sent {$sent} announcement(s).");
        return self::SUCCESS;
    }

    /** Deterministic ISO 1–5 (Mon–Fri) discount weekday for the given date's week. */
    private function chosenWeekday(Carbon $date): int
    {
        return (crc32($date->format('o-W') . ':dsdag') % 5) + 1;
    }

    /** A unique SnipperDag<random> code. Alphabet skips easily-confused characters. */
    private function freshCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $suffix = '';
            for ($i = 0; $i < 5; $i++) {
                $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $code = self::PREFIX . $suffix;
        } while (Coupon::where('code', $code)->exists());

        return $code;
    }

    /** Keep the admin Coupons list tidy: flip past SnipperDag codes back to inactive. */
    private function deactivateExpired(): void
    {
        Coupon::where('code', 'like', self::PREFIX . '%')
            ->where('is_active', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['is_active' => false]);
    }
}
