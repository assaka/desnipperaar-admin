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
 * of op een feestdag, dan schuift alleen die ene een week op, naar dezelfde weekdag.
 * De reeks zelf blijft op de oorspronkelijke data doorlopen, dus één verschuiving
 * sleept de rest niet mee.
 */
class GenerateSubscriptionPickups extends Command
{
    protected $signature = 'subscriptions:plan
                            {--days=90 : Hoe ver vooruit inplannen}
                            {--date= : Reken alsof het deze datum is (Y-m-d)}
                            {--subscription= : Alleen dit abonnement (id), voor direct na goedkeuren}
                            {--dry-run : Toon wat er zou gebeuren, maak niets aan}';

    protected $description = 'Plan de komende ophalingen van lopende abonnementen in';

    public function handle(): int
    {
        $today   = $this->option('date') ? \Carbon\Carbon::parse($this->option('date')) : now();
        $today   = $today->startOfDay();
        $horizon = $today->copy()->addDays((int) $this->option('days'));
        $dry     = (bool) $this->option('dry-run');

        $subscriptions = Order::where('type', Order::TYPE_ABONNEMENT)
            ->whereNotNull('sub_active_from')
            ->when($this->option('subscription'), fn ($q) => $q->whereKey((int) $this->option('subscription')))
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

                $pickupDate = WorkingDays::nextSameWeekday($scheduled);

                // Bij wekelijks of twee keer per week valt zo'n opgeschoven rit
                // op een dag waar al een ophaling staat. Twee keer op dezelfde
                // dag langsrijden heeft geen zin, dus die ene slaan we over: de
                // eerstvolgende reguliere ophaling vangt het op.
                if (! $pickupDate->equalTo($scheduled)) {
                    $bezet = Order::where('subscription_order_id', $sub->id)
                        ->whereDate('pickup_date', $pickupDate->toDateString())
                        ->exists();
                    if ($bezet) {
                        $this->warn(sprintf(
                            '%s ophaling %s valt op een feestdag; %s heeft al een rit, deze keer overgeslagen',
                            $sub->order_number,
                            $scheduled->format('d-m-Y'),
                            $pickupDate->format('d-m-Y'),
                        ));
                        continue;
                    }
                }

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
        // Het ankerpunt komt uit het model, zodat de activatiemail exact dezelfde
        // eerste datum noemt als hier wordt aangemaakt.
        $cursor = $sub->subFirstScheduledDate();
        if (! $cursor) {
            return;
        }

        if ($sub->sub_freq === '2pw') {
            while ($cursor->lessThanOrEqualTo($until)) {
                if (in_array($cursor->dayOfWeekIso, Order::TWICE_WEEKLY_ISO, true)) {
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

        while ($cursor->lessThanOrEqualTo($until)) {
            yield $cursor->copy();
            $cursor->addDays($interval);
        }
    }
}
