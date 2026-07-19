<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Support\WorkingDays;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;

/**
 * Zet de komende ophalingen van elk lopend abonnement als losse orders klaar.
 *
 * Een abonnement heeft zelf geen ophaaldatum en staat daarom niet op het
 * planbord. De bezoeken eronder zijn gewone orders met een datum, zodat ze
 * meelopen in de planning, een chauffeur krijgen, een bon en een certificaat.
 * Ze worden nooit los gefactureerd, zie de guards in Invoice::fromBon() en
 * BonController.
 *
 * Het ritme wordt geteld vanaf de ingangsdatum. Valt een ophaling in het weekend
 * of op een feestdag, dan schuift alleen die ene naar de eerstvolgende werkdag.
 * De reeks zelf blijft op de oorspronkelijke data doorlopen, dus één verschuiving
 * sleept de rest niet mee.
 */
class GenerateSubscriptionPickups extends Command
{
    protected $signature = 'subscriptions:plan
                            {--days=90 : Hoe ver vooruit inplannen}
                            {--date= : Reken alsof het deze datum is (Y-m-d)}
                            {--dry-run : Toon wat er zou gebeuren, maak niets aan}';

    protected $description = 'Plan de komende ophalingen van lopende abonnementen in';

    /** 2x per week is vast maandag en donderdag. */
    private const TWICE_WEEKLY_DAYS = [\Carbon\CarbonInterface::MONDAY, \Carbon\CarbonInterface::THURSDAY];

    public function handle(): int
    {
        $today   = $this->option('date') ? \Carbon\Carbon::parse($this->option('date')) : now();
        $today   = $today->startOfDay();
        $horizon = $today->copy()->addDays((int) $this->option('days'));
        $dry     = (bool) $this->option('dry-run');

        $subscriptions = Order::where('type', Order::TYPE_ABONNEMENT)
            ->whereNotNull('sub_active_from')
            ->orderBy('id')
            ->get();

        $made = 0;

        foreach ($subscriptions as $sub) {
            if ($sub->hasEnded()) {
                continue;
            }

            // Voorbij de opzegdatum hoeft er niets meer gepland te worden.
            $until = $sub->sub_ends_on && $sub->sub_ends_on->lessThan($horizon)
                ? $sub->sub_ends_on->copy()
                : $horizon;

            foreach ($this->slots($sub, $until) as $scheduled) {
                // Niet terugplannen. Wat in het verleden ligt is of gereden, of
                // gemist, en dat los je niet op met een order van vandaag.
                if ($scheduled->lessThan($today)) {
                    continue;
                }

                $exists = Order::where('subscription_order_id', $sub->id)
                    ->whereDate('subscription_scheduled_for', $scheduled->toDateString())
                    ->exists();
                if ($exists) {
                    continue;
                }

                $pickupDate = WorkingDays::next($scheduled);

                if ($dry) {
                    $shift = $pickupDate->equalTo($scheduled) ? '' : ' (verschoven van '.$scheduled->format('d-m-Y').')';
                    $this->line(sprintf('[dry] %s → ophaling %s%s', $sub->order_number, $pickupDate->format('d-m-Y'), $shift));
                    $made++;
                    continue;
                }

                try {
                    $child = Order::create([
                        'order_number'               => Order::generateOrderNumber(),
                        'type'                       => Order::TYPE_DIRECT,
                        'subscription_order_id'      => $sub->id,
                        'subscription_scheduled_for' => $scheduled->toDateString(),
                        'customer_id'                => $sub->customer_id,
                        'customer_name'              => $sub->customer_name,
                        'customer_email'             => $sub->customer_email,
                        'customer_phone'             => $sub->customer_phone,
                        'customer_address'           => $sub->customer_address,
                        'customer_postcode'          => $sub->customer_postcode,
                        'customer_city'              => $sub->customer_city,
                        'locale'                     => $sub->locale,
                        'delivery_mode'              => 'ophaal',
                        'container_count'            => 1,
                        'box_count'                  => 0,
                        'state'                      => Order::STATE_BEVESTIGD,
                        'pickup_date'                => $pickupDate->toDateString(),
                        'pickup_window'              => 'flexibel',
                        'notes'                      => 'Ophaling onder abonnement '.$sub->order_number
                                                        .' ('.$sub->subFreqLabel().'). Niet los factureren.',
                    ]);
                } catch (QueryException $e) {
                    // De unique index op (subscription_order_id, scheduled_for)
                    // heeft toegeslagen: al ingepland door een parallelle run.
                    continue;
                }

                $shift = $pickupDate->equalTo($scheduled) ? '' : ' (verschoven van '.$scheduled->format('d-m-Y').')';
                $this->info(sprintf('%s → %s op %s%s', $sub->order_number, $child->order_number, $pickupDate->format('d-m-Y'), $shift));
                $made++;
            }
        }

        $this->line(sprintf('%d ophaling(en) ingepland.', $made));

        return self::SUCCESS;
    }

    /**
     * De data die het ritme voorschrijft, vóór verschuiven. Geteld vanaf de
     * ingangsdatum, zodat het patroon vast ligt en niet afhangt van wanneer deze
     * command toevallig draait.
     *
     * @return \Generator<\Carbon\Carbon>
     */
    private function slots(Order $sub, \Carbon\Carbon $until): \Generator
    {
        $start = $sub->sub_active_from->copy()->startOfDay();

        if ($sub->sub_freq === '2pw') {
            // Vaste weekdagen. De ingangsdatum zelf telt mee als hij op een van
            // die dagen valt, anders begint het op de eerstvolgende.
            $cursor = $start->copy();
            while ($cursor->lessThanOrEqualTo($until)) {
                if (in_array($cursor->dayOfWeek, self::TWICE_WEEKLY_DAYS, true)) {
                    yield $cursor->copy();
                }
                $cursor->addDay();
            }

            return;
        }

        $interval = $sub->subIntervalDays();
        if (! $interval) {
            return;
        }

        // Verankeren op de afgesproken ophaaldag, niet op de ingangsdatum. De
        // eerste ophaling is de eerstvolgende keer dat die dag zich voordoet op
        // of na de ingangsdatum. Alle intervallen zijn een veelvoud van zeven
        // dagen, dus daarna blijft de reeks vanzelf op die weekdag staan.
        $weekday = $sub->subPickupWeekday() ?? $start->dayOfWeekIso;
        $cursor = $start->copy();
        while ($cursor->dayOfWeekIso !== $weekday) {
            $cursor->addDay();
        }

        while ($cursor->lessThanOrEqualTo($until)) {
            yield $cursor->copy();
            $cursor->addDays($interval);
        }
    }
}
