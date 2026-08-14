<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Http\Request;

/**
 * Een kortingscode toekennen aan een order die al bestaat.
 *
 * De publieke bestelstroom rekent de korting al in het cartoverzicht en hoogt
 * alleen times_used op; de code zelf landde nergens. Deze controller vult dat
 * gat voor de gevallen die daarna komen: de klant vergat zijn code, of er is
 * achteraf een korting toegezegd.
 *
 * Welke facturen meebewegen:
 *  - concept en verstuurd-maar-onbetaald worden herrekend. Bij verstuurd is dat
 *    een correctie voor de betaling, en dan is bijwerken eerlijker dan de klant
 *    laten betalen wat hij niet hoeft te betalen.
 *  - betaald, geannuleerd en creditfacturen blijven staan. Een betaalde factuur
 *    achteraf verlagen hoort via createCreditNote(), niet door het origineel te
 *    herschrijven; dat is een boekstuk dat de deur al uit is.
 */
class OrderCouponController extends Controller
{
    /** Facturen die een korting nog mogen volgen. */
    private const RECALCULABLE = [Invoice::STATUS_DRAFT, Invoice::STATUS_SENT];

    public function store(Request $request, Order $order)
    {
        $data = $request->validate([
            'coupon_code' => 'required|string|max:50',
        ]);

        // Een abonnement wordt per periode gefactureerd via fromSubscription().
        // Een korting daarop moet kiezen tussen eenmalig en elke periode, en dat
        // besluit is hier niet te nemen.
        if ($order->isAbonnement()) {
            return back()->withErrors([
                'coupon_code' => 'Een abonnement wordt per periode gefactureerd; een kortingscode hoort daar niet per order op.',
            ]);
        }

        // Een geannuleerde order wordt niet meer gefactureerd, dus een korting
        // erop wordt nergens verrekend.
        if ($order->isCanceled()) {
            return back()->withErrors([
                'coupon_code' => "Order {$order->order_number} is geannuleerd, er wordt niets meer gefactureerd.",
            ]);
        }

        // Een betaalde factuur wordt niet herrekend, dus een korting die er nu nog
        // op komt zou wel op de order staan maar nergens verrekend worden. Dat
        // levert een order op die een korting belooft die de klant nooit krijgt.
        if ($order->hasPaidInvoice()) {
            return back()->withErrors([
                'coupon_code' => "Order {$order->order_number} is al betaald, dus een kortingscode wordt niet meer verrekend. "
                    .'Moet er geld terug, boek dat via een creditfactuur.',
            ]);
        }

        $coupon = Coupon::findByCode($data['coupon_code']);
        if (!$coupon) {
            return back()->withErrors(['coupon_code' => 'Onbekende kortingscode.']);
        }

        $gross = $this->grossExclBtw($order);
        if ($gross <= 0) {
            return back()->withErrors(['coupon_code' => 'Deze order heeft nog geen bedrag om korting op te geven.']);
        }

        if (!$coupon->isValid($gross)) {
            return back()->withErrors(['coupon_code' => "Code {$coupon->code} is niet geldig voor deze order (verlopen, uitgeput, inactief of onder het minimumbedrag)."]);
        }

        $discount = round($coupon->discountFor($gross), 2);
        if ($discount <= 0) {
            return back()->withErrors(['coupon_code' => "Code {$coupon->code} levert op deze order geen korting op."]);
        }

        $order->update([
            'coupon_code'        => strtoupper(trim($coupon->code)),
            'coupon_discount'    => $discount,
            'coupon_applied_at'  => now(),
            'coupon_type'        => $coupon->type,
            'coupon_value'       => $coupon->value,
            'coupon_base'        => $gross,
            // De klant betaalt vanaf nu minder dan in zijn bevestiging staat.
            'confirmation_stale' => true,
        ]);
        $coupon->incrementUsage();

        $touched = $this->resync($order);

        $som = $coupon->type === 'percentage'
            ? \App\Support\Pricing::formatPercentage((float) $coupon->value).'% × € '.number_format($gross, 2, ',', '.').' = '
            : '';

        return back()->with('status', $this->summary(
            "Kortingscode {$order->coupon_code} toegekend: {$som}− € ".number_format($discount, 2, ',', '.'),
            $touched,
            $order,
        ));
    }

    public function destroy(Order $order)
    {
        if (!$order->coupon_code) {
            return back()->withErrors(['coupon_code' => 'Op deze order staat geen kortingscode.']);
        }

        // Na de ophaling heeft de klant het bedrag met korting al in handen. Dan is
        // intrekken geen correctie meer maar een prijsverhoging achteraf.
        if (!$order->canWithdrawCoupon()) {
            return back()->withErrors([
                'coupon_code' => "Order {$order->order_number} is al opgehaald, dus {$order->coupon_code} kan er niet meer af. "
                    .'Moet er geld terug of bij, boek dat via een creditfactuur.',
            ]);
        }

        $was = $order->coupon_code;
        $order->update([
            'coupon_code'        => null,
            'coupon_discount'    => null,
            'coupon_applied_at'  => null,
            'coupon_type'        => null,
            'coupon_value'       => null,
            'coupon_base'        => null,
            'confirmation_stale' => true,
        ]);

        // times_used blijft staan. Dat telt hoe vaak de code is ingezet en niet
        // hoeveel orders hem nu dragen; terugdraaien zou een uitgeputte code weer
        // bruikbaar maken op grond van een correctie.
        $touched = $this->resync($order);

        return back()->with('status', $this->summary("Kortingscode {$was} ingetrokken", $touched, $order));
    }

    /**
     * Herreken de facturen die de korting mogen volgen.
     *
     * @return array{updated: list<string>, skipped: list<string>}
     */
    private function resync(Order $order): array
    {
        $updated = [];
        $skipped = [];

        foreach ($order->invoices()->get() as $invoice) {
            if ($invoice->isCreditNote() || !in_array($invoice->status, self::RECALCULABLE, true)) {
                $skipped[] = $invoice->invoice_number.' ('.$invoice->status.')';
                continue;
            }
            $invoice->setRelation('order', $order);
            $invoice->syncCouponLine();
            $updated[] = $invoice->invoice_number;
        }

        return ['updated' => $updated, 'skipped' => $skipped];
    }

    /**
     * Het bedrag excl. btw waar de korting op gerekend wordt.
     *
     * De ophaalkosten en de spoedtoeslag tellen niet mee. Het winkelwagentje op
     * /order rekent zo, en sinds de code daar ook echt op de order landt hoort
     * deze weg dezelfde uitkomst te geven. Anders kreeg wie zijn code vergat en
     * hem hier met de hand kreeg toegekend meer korting dan wie hem meteen
     * invulde, en dat verschil is niet uit te leggen.
     */
    private function grossExclBtw(Order $order): float
    {
        // Bestaat er al een factuur, dan is dat het bedrag dat de klant te zien
        // krijgt; die telt zwaarder dan een herberekening uit de order.
        $invoice = $order->invoices()
            ->whereNull('credits_invoice_id')
            ->whereIn('status', self::RECALCULABLE)
            ->orderByDesc('id')
            ->first();

        if ($invoice) {
            return $invoice->grossExclBtw();
        }

        return (float) \App\Support\Pricing::snapshot(
            (int) $order->box_count,
            (int) $order->container_count,
            $order->media_items,
            (bool) $order->pilot,
            (bool) $order->first_box_free,
            0.0,   // ophaalkosten staan buiten de grondslag
            0.0,
        )['subtotal'];
    }

    private function summary(string $head, array $touched, Order $order): string
    {
        $parts = [$head];

        if ($touched['updated']) {
            $parts[] = 'factuur '.implode(', ', $touched['updated']).' herrekend';
        }
        if ($touched['skipped']) {
            $parts[] = 'ongemoeid gelaten: '.implode(', ', $touched['skipped']);
        }
        if (!$touched['updated'] && !$touched['skipped']) {
            $parts[] = 'er is nog geen factuur, de korting gaat mee zodra die wordt aangemaakt';
        }

        return implode('. ', $parts).'.';
    }
}
