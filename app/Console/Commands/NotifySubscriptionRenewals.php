<?php

namespace App\Console\Commands;

use App\Mail\SubscriptionRenewalNotice;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Eén maand voor het einde van een Vast- of Jaartermijn de klant mailen.
 *
 * Alleen Vast en Jaar hebben een termijn die afloopt. Flex en de losse
 * maandtermijn lopen door tot iemand opzegt, daar valt niets te verlengen.
 *
 * Er wordt gemaild in een venster van een paar dagen in plaats van precies op
 * dag dertig, zodat een nacht zonder cron geen klant overslaat. Dubbel mailen
 * kan niet, want sub_renewal_notified_at wordt gezet zodra de mail eruit is.
 */
class NotifySubscriptionRenewals extends Command
{
    protected $signature = 'subscriptions:renewal-notice
                            {--date= : Reken alsof het deze datum is (Y-m-d)}
                            {--dry-run : Toon wat er zou gebeuren, verstuur niets}';

    protected $description = 'Mail klanten een maand voor het aflopen van hun Vast- of Jaartermijn';

    /** Venster in dagen voor de verlengdatum waarin de mail mag vertrekken. */
    private const WINDOW_FROM = 23;
    private const WINDOW_TO   = 31;

    public function handle(): int
    {
        $today = $this->option('date') ? \Carbon\Carbon::parse($this->option('date')) : now();
        $today = $today->startOfDay();
        $dry   = (bool) $this->option('dry-run');

        $subscriptions = Order::where('type', Order::TYPE_ABONNEMENT)
            ->whereIn('sub_term', ['vast', 'jaar'])
            ->whereNotNull('sub_active_from')
            ->whereNull('sub_renewal_notified_at')
            ->whereNull('sub_terminated_at')
            ->orderBy('id')
            ->get();

        $sent = 0;

        foreach ($subscriptions as $order) {
            if ($order->hasEnded()) {
                continue;
            }

            $renewal = $order->subRenewalDate();
            if (! $renewal) {
                continue;
            }

            $daysOut = $today->diffInDays($renewal, false);
            if ($daysOut < self::WINDOW_FROM || $daysOut > self::WINDOW_TO) {
                continue;
            }

            if ($dry) {
                $this->line(sprintf(
                    '[dry] %s %s termijn %s loopt af %s (over %d dagen)',
                    $order->order_number,
                    $order->customer_name,
                    $order->sub_term,
                    $renewal->format('d-m-Y'),
                    $daysOut,
                ));
                $sent++;
                continue;
            }

            try {
                Mail::to($order->customer_email)->send(new SubscriptionRenewalNotice($order));
            } catch (\Throwable $e) {
                // Niet markeren als gemaild, dan probeert de cron het morgen
                // opnieuw. Het venster is ruim genoeg voor een paar pogingen.
                report($e);
                $this->error(sprintf('%s mail mislukt, morgen opnieuw', $order->order_number));
                continue;
            }

            $order->update(['sub_renewal_notified_at' => now()]);

            $this->info(sprintf('%s → %s, termijn loopt af %s', $order->order_number, $order->customer_email, $renewal->format('d-m-Y')));
            $sent++;
        }

        $this->line(sprintf('%d verlengmail(s) verstuurd.', $sent));

        return self::SUCCESS;
    }
}
