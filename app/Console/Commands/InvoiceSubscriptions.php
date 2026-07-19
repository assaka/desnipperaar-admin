<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;

/**
 * Factureer lopende abonnementen, vooruit, per periode van 4 weken.
 *
 * De prijs is per 4 weken, zoals geadverteerd, niet per kalendermaand. De eerste
 * periode start op de bezorgdag en loopt precies 28 dagen (52 weken bij
 * jaarbetaling), dus die loopt altijd vol, geen deelperiode aan het begin. Er
 * wordt vooruit gefactureerd, dus zodra een periode is begonnen (of vandaag
 * begint) mag de factuur eruit. Een deelperiode ontstaat alleen nog bij opzeggen
 * midden in een periode.
 *
 * Facturen worden als concept aangemaakt, niet verstuurd. Geld dat vanzelf de
 * deur uit gaat zonder dat iemand ernaar heeft gekeken is een groter risico dan
 * een dag vertraging. Versturen gaat via de bestaande knop op de factuurpagina.
 * Zodra automatische incasso via Stripe live staat, vervangt dat dit pad, en
 * blijft de periode- en bedragberekening in Invoice::fromSubscription staan.
 */
class InvoiceSubscriptions extends Command
{
    protected $signature = 'subscriptions:invoice
                            {--date= : Reken alsof het deze datum is (Y-m-d), voor herstel en tests}
                            {--dry-run : Toon wat er zou gebeuren, maak niets aan}';

    protected $description = 'Maak conceptfacturen voor lopende abonnementen (vooruit, per 4 weken)';

    public function handle(): int
    {
        $today = $this->option('date') ? \Carbon\Carbon::parse($this->option('date')) : now();
        $today = $today->startOfDay();
        $dry   = (bool) $this->option('dry-run');

        $subscriptions = Order::where('type', Order::TYPE_ABONNEMENT)
            ->whereNotNull('sub_active_from')
            ->orderBy('id')
            ->get();

        $made = 0;
        $skipped = 0;

        foreach ($subscriptions as $order) {
            // Afgelopen abonnementen krijgen niets meer. hasEnded() kijkt naar
            // sub_ends_on, dus een opgezegd abonnement loopt gewoon door tot die
            // datum en wordt tot dan nog normaal gefactureerd.
            if ($order->hasEnded()) {
                continue;
            }

            // Vast en Jaar lopen na hun termijn maandelijks door, tegen het
            // maandtarief van Vast. Dat omzetten gebeurt hier, vóór het bepalen
            // van de volgende periode: anders zou een Jaar na afloop nog een
            // heel jaar vooruit gefactureerd worden, terwijl de klant is beloofd
            // dat hij dan maandelijks kan stoppen.
            $renewal = $order->subRenewalDate();
            if ($renewal && $today->greaterThan($renewal) && ! $order->sub_terminated_at) {
                $wasTerm = $order->sub_term;
                if (! $dry) {
                    $order->convertToMonthly();
                    $order->refresh();
                }
                $this->warn(sprintf(
                    '%s termijn %s afgelopen op %s, omgezet naar maandelijks',
                    $order->order_number,
                    $wasTerm,
                    $renewal->format('d-m-Y'),
                ));
            }

            $periodStart = $this->nextPeriodStart($order);

            if ($periodStart->greaterThan($today)) {
                $skipped++;
                continue;
            }

            // Voorbij de einddatum bestaat er geen periode meer om te factureren.
            if ($order->sub_ends_on && $periodStart->greaterThan($order->sub_ends_on)) {
                continue;
            }

            if ($dry) {
                $this->line(sprintf(
                    '[dry] %s %s periode vanaf %s',
                    $order->order_number,
                    $order->customer_name,
                    $periodStart->format('Y-m-d'),
                ));
                $made++;
                continue;
            }

            // Zodra online incasso live staat is dit de plek waar het uiteenloopt:
            // een abonnement met een sub_billing_ref wordt door de aggregator
            // geïncasseerd en hoort hier geen tweede rekening te krijgen. De
            // periode- en bedragberekening in Invoice::fromSubscription blijft
            // dan gewoon staan, alleen het innen verhuist.
            if ($order->sub_billing_ref) {
                $this->line(sprintf('%s loopt via %s, overgeslagen', $order->order_number, $order->sub_billing_provider ?: 'externe incasso'));
                $skipped++;
                continue;
            }

            try {
                $invoice = Invoice::fromSubscription($order, $periodStart);
            } catch (QueryException $e) {
                // De unique index op (order_id, period_start) heeft toegeslagen:
                // deze periode was al gefactureerd. Dan klopt alleen de stand op
                // de order niet meer, dus die trekken we recht en gaan door.
                $order->update(['sub_last_invoiced_period' => $periodStart->toDateString()]);
                $this->warn(sprintf('%s periode %s bestond al, overgeslagen', $order->order_number, $periodStart->format('Y-m-d')));
                $skipped++;
                continue;
            }

            $order->update(['sub_last_invoiced_period' => $periodStart->toDateString()]);

            $this->info(sprintf(
                '%s → %s (%s t/m %s) € %s',
                $order->order_number,
                $invoice->invoice_number,
                $invoice->period_start->format('d-m-Y'),
                $invoice->period_end->format('d-m-Y'),
                number_format((float) $invoice->amount_incl_btw, 2, ',', '.'),
            ));
            $made++;
        }

        $this->line(sprintf('%d factuur(en) aangemaakt, %d overgeslagen.', $made, $skipped));

        return self::SUCCESS;
    }

    /**
     * De eerste nog niet gefactureerde periode. Zonder eerdere factuur is dat de
     * bezorgdag zelf, en daar begint de eerste periode van 4 weken.
     */
    private function nextPeriodStart(Order $order): \Carbon\Carbon
    {
        if (! $order->sub_last_invoiced_period) {
            return $order->sub_active_from->copy()->startOfDay();
        }

        // Aansluiten op het einde van de vorige periode, en dat einde uit het
        // model halen. Zelf nog een keer "plus een maand" of "plus een jaar"
        // uitrekenen zou een tweede definitie van een periode opleveren, en die
        // twee lopen vroeg of laat uit elkaar.
        return $order->subPeriodEnd($order->sub_last_invoiced_period->copy())
            ->addDay()
            ->startOfDay();
    }
}
