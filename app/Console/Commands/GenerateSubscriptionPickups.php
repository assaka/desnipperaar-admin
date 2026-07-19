<?php

namespace App\Console\Commands;

use App\Models\Bon;
use App\Models\Order;
use App\Support\WorkingDays;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;

/**
 * Zet de komende ophalingen van elk lopend abonnement klaar als bons.
 *
 * Een abonnement is één order: één afspraak met de klant. De ritten eronder zijn
 * bons op diezelfde order, en elke vernietiging levert een certificaat bij die
 * bon. Eerder kreeg elke ophaling een eigen order, waardoor er bij één klant een
 * dozijn orders in de lijst stond voor wat één abonnement is.
 *
 * Het ritme wordt geteld vanaf de bezorgdag. Valt een ophaling in het weekend of
 * op een feestdag, dan schuift alleen die ene een week op, naar dezelfde weekdag.
 * De reeks blijft op de oorspronkelijke data doorlopen, dus één verschuiving
 * sleept de rest niet mee. scheduled_for bewaart die oorspronkelijke datum, zodat
 * een verschoven rit niet elke nacht opnieuw wordt aangemaakt.
 */
class GenerateSubscriptionPickups extends Command
{
    protected $signature = 'subscriptions:plan
                            {--days=90 : Hoe ver vooruit inplannen}
                            {--date= : Reken alsof het deze datum is (Y-m-d)}
                            {--subscription= : Alleen dit abonnement (id), voor direct na goedkeuren}
                            {--dry-run : Toon wat er zou gebeuren, maak niets aan}';

    protected $description = 'Plan de komende ophalingen van lopende abonnementen in als bons';

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

            $until = $sub->sub_ends_on && $sub->sub_ends_on->lessThan($horizon)
                ? $sub->sub_ends_on->copy()
                : $horizon;

            foreach ($this->slots($sub, $until) as $scheduled) {
                // Niet terugplannen. Wat in het verleden ligt is of gereden, of
                // gemist, en dat los je niet op met een rit van vandaag.
                if ($scheduled->lessThan($today)) {
                    continue;
                }

                if ($sub->bons()->whereDate('scheduled_for', $scheduled->toDateString())->exists()) {
                    continue;
                }

                $plannedFor = WorkingDays::nextSameWeekday($scheduled);

                // Bij wekelijks of twee keer per week valt zo'n opgeschoven rit op
                // een dag waar al een bon staat. Twee keer op dezelfde dag
                // langsrijden heeft geen zin, dus die ene slaan we over.
                if (! $plannedFor->equalTo($scheduled)
                    && $sub->bons()->whereDate('planned_for', $plannedFor->toDateString())->exists()) {
                    $this->warn(sprintf(
                        '%s ophaling %s valt op een feestdag; %s heeft al een rit, deze keer overgeslagen',
                        $sub->order_number,
                        $scheduled->format('d-m-Y'),
                        $plannedFor->format('d-m-Y'),
                    ));
                    continue;
                }

                $shift = $plannedFor->equalTo($scheduled) ? '' : ' (verschoven van '.$scheduled->format('d-m-Y').')';

                if ($dry) {
                    $this->line(sprintf('[dry] %s → ophaling %s%s', $sub->order_number, $plannedFor->format('d-m-Y'), $shift));
                    $made++;
                    continue;
                }

                try {
                    $bon = Bon::create([
                        'bon_number'     => Bon::generateBonNumber(),
                        'order_id'       => $sub->id,
                        'mode'           => Bon::MODE_OPHAAL,
                        'planned_for'    => $plannedFor->toDateString(),
                        'planned_window' => 'flexibel',
                        'scheduled_for'  => $scheduled->toDateString(),
                    ]);
                } catch (QueryException $e) {
                    // De unique index op (order_id, scheduled_for) heeft
                    // toegeslagen: al ingepland door een parallelle run.
                    continue;
                }

                $this->info(sprintf('%s → bon %s op %s%s', $sub->order_number, $bon->bon_number, $plannedFor->format('d-m-Y'), $shift));
                $made++;
            }
        }

        $this->line(sprintf('%d ophaling(en) ingepland.', $made));

        return self::SUCCESS;
    }

    /**
     * De data die het ritme voorschrijft, vóór verschuiven.
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
