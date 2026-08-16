<?php

namespace App\Console\Commands;

use App\Models\Coupon;
use Illuminate\Console\Command;

/**
 * The order page mints a SNIP24 code per visitor per day, so the coupons table
 * grows by roughly one row per visitor. Once such a code has expired unused it
 * carries no information, and left alone it would bury the hand-made coupons in
 * the admin list within weeks.
 *
 * Codes that were actually redeemed are kept: an order references the code it
 * was placed with, and the invoice has to stay explainable afterwards.
 */
class PruneIssuedCoupons extends Command
{
    protected $signature = 'coupons:prune-issued
        {--days=7 : Keep expired unused codes this many days before deleting}
        {--dry-run : Report what would be deleted without deleting it}';

    protected $description = 'Delete expired, unused auto-issued order-page coupons.';

    public function handle(): int
    {
        $cutoff = now()->subDays((int) $this->option('days'));

        $query = Coupon::where('code', 'LIKE', 'SNIP24%')
            ->whereNotNull('issued_ip_hash')
            ->where('times_used', 0)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', $cutoff);

        $count = (clone $query)->count();

        if ($this->option('dry-run')) {
            $this->info("Dry run: {$count} expired unused code(s) would be deleted.");
            return self::SUCCESS;
        }

        $query->delete();
        $this->info("Deleted {$count} expired unused code(s).");

        return self::SUCCESS;
    }
}
