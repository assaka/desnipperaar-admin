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
 * differs from week to week. On that day the command activates the DSDAG35
 * coupon (expiring at midnight) and e-mails every active subscriber in their own
 * language. Idempotent: the unique dag_announcements row stops a double send.
 */
class SendDagAnnouncement extends Command
{
    protected $signature = 'desnipperaar:dag-announce
        {--force : Treat today as the Dag and resend, ignoring the weekday pick and the already-sent guard}
        {--dry-run : Report what would happen without activating the coupon or sending mail}';

    protected $description = 'Announce DeSnipperaar Dag to subscribers when today is this week\'s random discount day.';

    private const CODE = 'DSDAG35';

    public function handle(): int
    {
        $today  = now('Europe/Amsterdam');
        $chosen = $this->chosenWeekday($today);
        $isDay  = $today->dayOfWeekIso === $chosen || $this->option('force');

        $this->line("This week's DeSnipperaar Dag falls on " . Carbon::now('Europe/Amsterdam')
            ->startOfWeek()->addDays($chosen - 1)->locale('nl')->translatedFormat('l') . '.');

        if (! $isDay) {
            $this->deactivateIfExpired();
            $this->info('Today is not the Dag. Nothing to send.');
            return self::SUCCESS;
        }

        $coupon = Coupon::where('code', self::CODE)->first();
        if (! $coupon) {
            $this->error('Coupon ' . self::CODE . ' does not exist. Run migrations / seed it first.');
            return self::FAILURE;
        }
        $pct = (int) round((float) $coupon->value);

        $already = DagAnnouncement::whereDate('announced_on', $today->toDateString())->exists();
        if ($already && ! $this->option('force')) {
            $this->info('Already announced today. Skipping.');
            return self::SUCCESS;
        }

        $recipients = Subscriber::active()->whereNotNull('unsubscribe_token')->get();
        $this->info("DeSnipperaar Dag is today. {$recipients->count()} active subscriber(s), {$pct}% via {$coupon->code}.");

        if ($this->option('dry-run')) {
            $this->warn('Dry run: coupon not activated, no mail sent.');
            return self::SUCCESS;
        }

        // Activate the code for today only.
        $coupon->update(['is_active' => true, 'expires_at' => $today->copy()->endOfDay()]);

        $sent = 0;
        foreach ($recipients as $sub) {
            try {
                Mail::to($sub->email)->queue(new DesnipperaarDagAnnouncement($sub, $coupon->code, $pct));
                $sent++;
            } catch (\Throwable $e) {
                report($e);
                $this->error("  failed for {$sub->email}: {$e->getMessage()}");
            }
        }

        DagAnnouncement::updateOrCreate(
            ['announced_on' => $today->toDateString()],
            ['recipients' => $sent],
        );

        $this->info("Queued {$sent} announcement(s).");
        return self::SUCCESS;
    }

    /** Deterministic ISO 1–5 (Mon–Fri) discount weekday for the given date's week. */
    private function chosenWeekday(Carbon $date): int
    {
        return (crc32($date->format('o-W') . ':dsdag') % 5) + 1;
    }

    /** Keep the admin Coupons list tidy: flip DSDAG35 back to inactive once its day has passed. */
    private function deactivateIfExpired(): void
    {
        $coupon = Coupon::where('code', self::CODE)->first();
        if ($coupon && $coupon->is_active && $coupon->expires_at && $coupon->expires_at->isPast()) {
            $coupon->update(['is_active' => false]);
        }
    }
}
