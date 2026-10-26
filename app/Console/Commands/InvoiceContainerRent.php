<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;

/**
 * Huur voor losse rolcontainers die bij de klant blijven staan.
 *
 * De huurmaanden tellen vanaf container_keep_since, de dag van de rit waarop de
 * container bleef staan. Maand één staat op de orderfactuur, dus dit begint bij
 * maand twee: een maand na die dag. Een maand die begonnen is wordt vooruit
 * gefactureerd, heel, ook als de container halverwege wordt opgehaald.
 *
 * Stopt zodra container_collected_on is gezet en die dag op of voor het begin
 * van de volgende maand ligt.
 *
 * Facturen worden als concept aangemaakt en niet verstuurd, net als losse
 * orders: versturen gaat via de knop op de factuurpagina. Het abonnement
 * verstuurt zelf, maar daar is het bedrag een afgesproken vaste prijs met een
 * eigen bevestiging; hier is het een nieuwe stroom, dus eerst een paar maanden
 * meekijken.
 */
class InvoiceContainerRent extends Command
{
    protected $signature = 'containers:invoice-rent
                            {--date= : Reken alsof het deze datum is (Y-m-d), voor herstel en tests}
                            {--order= : Alleen deze order (id)}
                            {--dry-run : Toon wat er zou gebeuren, maak niets aan}';

    protected $description = 'Maak conceptfacturen voor de maandhuur van rolcontainers die bij de klant staan';

    public function handle(): int
    {
        $today = $this->option('date') ? \Carbon\Carbon::parse($this->option('date')) : now();
        $today = $today->copy()->startOfDay();
        $dry   = (bool) $this->option('dry-run');

        $orders = Order::where('container_keep', true)
            ->whereNotNull('container_keep_since')
            ->where('state', '!=', Order::STATE_GEANNULEERD)
            ->when($this->option('order'), fn ($q) => $q->whereKey((int) $this->option('order')))
            ->orderBy('id')
            ->get();

        $made = 0;

        foreach ($orders as $order) {
            if (! $order->keepsContainer()) {
                continue;
            }

            // Inhalen wat er nog openstaat, maand voor maand. Normaal is dat er
            // hooguit één, maar na een gemiste nacht of met --date kunnen het er
            // meer zijn.
            while (true) {
                [$start, $end] = $this->nextPeriod($order);

                if ($start->greaterThan($today)) {
                    break;
                }
                if ($order->container_collected_on && $order->container_collected_on->copy()->startOfDay()->lessThanOrEqualTo($start)) {
                    break;
                }

                if ($dry) {
                    $this->line(sprintf('[dry] %s %s huur vanaf %s', $order->order_number, $order->customer_name, $start->format('Y-m-d')));
                    $made++;
                    break;
                }

                try {
                    $invoice = Invoice::fromContainerRent($order, $start, $end);
                } catch (QueryException $e) {
                    // De unique index op (order_id, period_start): deze maand
                    // stond er al. Niet nog eens proberen.
                    $this->warn(sprintf('%s maand %s bestond al, overgeslagen', $order->order_number, $start->format('Y-m-d')));
                    break;
                }

                $this->info(sprintf(
                    '%s → %s (%s t/m %s) € %s · concept',
                    $order->order_number,
                    $invoice->invoice_number,
                    $invoice->period_start->format('d-m-Y'),
                    $invoice->period_end->format('d-m-Y'),
                    number_format((float) $invoice->amount_incl_btw, 2, ',', '.'),
                ));
                $made++;
            }
        }

        $this->line(sprintf('%d huurfactuur(en) aangemaakt.', $made));

        return self::SUCCESS;
    }

    /**
     * De eerste nog niet gefactureerde huurmaand, als [begin, eind].
     *
     * Maand k loopt van anker + k maanden tot de dag voor anker + k+1 maanden,
     * steeds gerekend vanaf container_keep_since en niet vanaf het einde van de
     * vorige factuur. Anders schuift een anker op de 31e via februari blijvend
     * naar de 28e. Maand 0 staat op de orderfactuur, dus k begint bij 1.
     */
    private function nextPeriod(Order $order): array
    {
        $k = 1 + $order->invoices()->whereNotNull('period_start')->count();
        $anchor = $order->container_keep_since->copy()->startOfDay();

        return [
            $anchor->copy()->addMonthsNoOverflow($k),
            $anchor->copy()->addMonthsNoOverflow($k + 1)->subDay(),
        ];
    }
}
