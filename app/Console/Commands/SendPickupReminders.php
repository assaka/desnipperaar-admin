<?php

namespace App\Console\Commands;

use App\Mail\DeliveryReminder;
use App\Mail\PickupReminder;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Herinnering de dag voor een ophaling onder een abonnement.
 *
 * De planner maakt de ophalingen stil aan, dus zonder deze mail hoort de klant
 * na de activatiemail helemaal niets meer en staat er ineens een bus voor de
 * deur. Bij een ophaaldag die door een feestdag is verschoven is het nog
 * belangrijker, want dan wijkt de dag af van wat de klant gewend is.
 *
 * Alleen abonnementsophalingen. Losse orders krijgen bij het inplannen al een
 * bevestiging met datum via PickupConfirmed, dus die hebben dit niet nodig.
 */
class SendPickupReminders extends Command
{
    protected $signature = 'subscriptions:remind
                            {--date= : Reken alsof het deze datum is (Y-m-d)}
                            {--dry-run : Toon wat er zou gebeuren, verstuur niets}';

    protected $description = 'Mail klanten de dag voor hun abonnementsophaling';

    public function handle(): int
    {
        $today = $this->option('date') ? \Carbon\Carbon::parse($this->option('date')) : now();
        $today = $today->startOfDay();
        $dry   = (bool) $this->option('dry-run');

        $tomorrow = $today->copy()->addDay();

        // Bezorgingen én ophalingen, maar elk met hun eigen mail. Eén tekst voor
        // beide zou bij een bezorging vragen om een container buiten te zetten
        // die er nog niet staat.
        $pickups = Order::whereNotNull('subscription_order_id')
            ->whereNull('pickup_reminder_sent_at')
            ->whereDate('pickup_date', $tomorrow->toDateString())
            ->whereIn('state', [Order::STATE_NIEUW, Order::STATE_BEVESTIGD])
            ->with('subscription')
            ->orderBy('id')
            ->get();

        $sent = 0;

        foreach ($pickups as $pickup) {
            if (! $pickup->customer_email) {
                continue;
            }

            $isDelivery = $pickup->isBezorging();

            if ($dry) {
                $this->line(sprintf(
                    '[dry] %s %s %s → %s op %s',
                    $pickup->order_number,
                    $isDelivery ? 'BRENGEN' : 'ophalen',
                    $pickup->customer_name,
                    $pickup->customer_email,
                    $pickup->pickup_date->format('d-m-Y'),
                ));
                $sent++;
                continue;
            }

            try {
                Mail::to($pickup->customer_email)->send(
                    $isDelivery ? new DeliveryReminder($pickup) : new PickupReminder($pickup)
                );
            } catch (\Throwable $e) {
                // Niet markeren, dan is er morgenvroeg nog een kans. Daarna is de
                // ophaaldag zelf en heeft een herinnering geen zin meer.
                report($e);
                $this->error(sprintf('%s mail mislukt', $pickup->order_number));
                continue;
            }

            $pickup->update(['pickup_reminder_sent_at' => now()]);

            $this->info(sprintf('%s → %s (%s)', $pickup->order_number, $pickup->customer_email, $pickup->pickup_date->format('d-m-Y')));
            $sent++;
        }

        $this->line(sprintf('%d herinnering(en) verstuurd.', $sent));

        return self::SUCCESS;
    }
}
