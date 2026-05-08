<?php

namespace App\Console\Commands;

use App\Mail\OrderCreated;
use App\Models\GroupDeal;
use App\Models\Order;
use App\Support\Pricing;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Closes group deals whose join cutoff has passed (T-N days before pickup_date,
 * controlled by config('desnipperaar.group_deal.join_cutoff_days')) and materializes
 * one Order per participant. Idempotent: deals already in 'closed' or 'completed'
 * are skipped, and per-participant Order creation is guarded by participant.order_id.
 */
class CloseGroupDeals extends Command
{
    protected $signature = 'group-deals:close
        {--dry-run : List affected deals without writing changes}
        {--deal= : Close this specific deal id, ignoring the join-cutoff date filter (admin manual close)}';
    protected $description = 'Close open group deals past their join cutoff and materialize orders for each participant.';

    public function handle(): int
    {
        $dealId = $this->option('deal');

        if ($dealId) {
            // Manual close from admin — force this specific open deal regardless of
            // pickup_date. Useful when admin needs to close early (e.g. target met).
            $deals = GroupDeal::where('status', GroupDeal::STATUS_OPEN)
                ->where('id', (int) $dealId)
                ->get();
            if ($deals->isEmpty()) {
                $this->error("Deal #{$dealId} is not open or does not exist.");
                return self::FAILURE;
            }
        } else {
            // Cron path: only deals whose pickup_date is within join_cutoff_days.
            $cutoffDays = (int) config('desnipperaar.group_deal.join_cutoff_days', 2);
            $today      = now()->startOfDay();
            $deals = GroupDeal::where('status', GroupDeal::STATUS_OPEN)
                ->whereDate('pickup_date', '<=', $today->copy()->addDays($cutoffDays)->toDateString())
                ->get();
        }

        if ($deals->isEmpty()) {
            $this->info('No deals due for closing.');
            return self::SUCCESS;
        }

        foreach ($deals as $deal) {
            $this->info("Deal #{$deal->id} ({$deal->city}, {$deal->pickup_date->toDateString()}): "
                . $deal->participants()->count() . ' participants');

            if ($this->option('dry-run')) {
                continue;
            }

            $this->closeDeal($deal);
        }

        return self::SUCCESS;
    }

    private function closeDeal(GroupDeal $deal): void
    {
        DB::transaction(function () use ($deal) {
            $participants = $deal->participants()->whereNull('order_id')->get();

            foreach ($participants as $p) {
                $isOrganizer = $p->id === $deal->organizer_participant_id;
                $isPilot     = Pricing::isPilotPostcode($p->customer_postcode);
                $perkType    = config('desnipperaar.group_deal.organizer_perk_type');
                $firstBoxFree = $isOrganizer && $perkType === 'first_box_free' && !$isPilot;

                $orderNumber = Order::generateOrderNumber();
                $order = Order::create([
                    'order_number'      => $orderNumber,
                    'type'              => Order::TYPE_DIRECT,
                    'group_deal_id'     => $deal->id,
                    'is_organizer'      => $isOrganizer,
                    'quote_locked'      => true,
                    'price_snapshot'    => $p->price_snapshot,

                    'customer_name'     => $p->customer_name,
                    'customer_email'    => $p->customer_email,
                    'customer_phone'    => $p->customer_phone,
                    'customer_address'  => $p->customer_address,
                    'customer_postcode' => $p->customer_postcode,
                    'customer_city'     => $p->customer_city ?? $deal->city,

                    'delivery_mode'     => 'ophaal',
                    'box_count'         => $p->box_count,
                    'container_count'   => $p->container_count,
                    'media_items'       => $p->media_items,
                    'notes'             => $p->notes,

                    'state'             => Order::STATE_BEVESTIGD,
                    'pilot'             => $isPilot,
                    'first_box_free'    => $firstBoxFree,
                    'pickup_date'       => $deal->pickup_date,
                    'quoted_amount_excl_btw' => $p->price_snapshot['subtotal'] ?? null,
                ]);

                $p->update(['order_id' => $order->id]);

                try {
                    Mail::to($p->customer_email)->send(new OrderCreated($order));
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            $deal->update([
                'status'    => GroupDeal::STATUS_CLOSED,
                'closed_at' => now(),
            ]);
        });

        $this->info("→ closed deal #{$deal->id}, materialized "
            . $deal->participants()->whereNotNull('order_id')->count() . ' orders');
    }
}
