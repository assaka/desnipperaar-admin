<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\PickupConfirmed;
use App\Mail\PickupPlannedByCustomer;
use App\Models\Order;
use App\Services\SlotFinder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

/**
 * Token-gated JSON API achter de planpagina op desnipperaar.nl. De klant kiest
 * daar zelf zijn ophaalmoment uit de momenten die wij kunnen rijden.
 *
 * Het verschil met RescheduleController is wie het laatste woord heeft. Een
 * herplanning is een verzoek: de klant noemt een dag, wij kijken of het kan. Hier
 * kijken wij eerst en biedt de klant niets aan, hij kiest uit wat al kan. Daarom
 * staat de datum meteen op de order en gaat er een bevestiging uit, in plaats van
 * "wij laten het u weten".
 *
 * De pagina is bewust nog nergens vandaan gelinkt.
 */
class PickupPlanController extends Controller
{
    public function show(string $token, SlotFinder $finder): JsonResponse
    {
        $order = Order::where('public_token', $token)->first();
        if (! $order) {
            return response()->json(['error' => 'not_found'], 404);
        }

        return response()->json($this->payload($order, $finder));
    }

    public function store(Request $request, string $token, SlotFinder $finder): JsonResponse
    {
        $order = Order::where('public_token', $token)->first();
        if (! $order) {
            return response()->json(['error' => 'not_found'], 404);
        }

        if ($this->status($order) !== 'ok') {
            return response()->json(['error' => 'closed', 'status' => $this->status($order)], 403);
        }

        $validator = Validator::make($request->all(), [
            'date'   => 'required|date_format:Y-m-d',
            'window' => 'required|in:ochtend,middag,avond',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $data = $validator->validated();

        // Opnieuw narekenen in plaats van de klant op zijn woord geloven. Tussen
        // het tonen van de lijst en deze klik kan er een andere ophaling zijn
        // bijgekomen, en dan is het moment weg. Een 409 stuurt de pagina terug
        // met een verse lijst.
        $result = $finder->forOrder($order);
        $ceiling = $this->priceCeiling($order);
        if (! $finder->isOffered($result['slots'], $data['date'], $data['window'], $ceiling)) {
            return response()->json([
                'error' => 'slot_taken',
                'slots' => $finder->offer($result['slots'], null, $ceiling),
            ], 409);
        }

        $order->update([
            'pickup_date'                   => $data['date'],
            'pickup_window'                 => $data['window'],
            'pickup_planned_by_customer_at' => now(),
            // Een openstaand wijzigingsverzoek is achterhaald zodra de klant zelf
            // een moment kiest.
            'reschedule_requested_at'     => null,
            'reschedule_requested_date'   => null,
            'reschedule_requested_window' => null,
            'reschedule_notes'            => null,
        ]);

        $fresh = $order->fresh()->load('customer');

        try {
            Mail::to($order->customer_email)->send(new PickupConfirmed($fresh));
        } catch (\Throwable $e) {
            report($e);
        }

        $adminEmail = config('desnipperaar.notifications.admin_email');
        if ($adminEmail) {
            try {
                Mail::to($adminEmail)->send(new PickupPlannedByCustomer($fresh));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response()->json([
            'ok'     => true,
            'pickup' => [
                'date'   => $fresh->pickup_date?->toDateString(),
                'window' => $fresh->pickup_window,
            ],
        ]);
    }

    private function payload(Order $order, SlotFinder $finder): array
    {
        $status = $this->status($order);

        // Alleen zoeken als er iets te kiezen valt. Bij een opgehaalde of
        // afgesloten order is de lijst niet alleen nutteloos, hij kost ook een
        // ronde geocoderen.
        $slots = [];
        $depotKm = null;
        if ($status === 'ok') {
            $result = $finder->forOrder($order);
            $slots = $finder->offer($result['slots'], null, $this->priceCeiling($order));
            $depotKm = $result['depot_km'];
        }

        return [
            'order_number'      => $order->order_number,
            'locale'            => in_array($order->locale, ['nl', 'en', 'fr', 'es'], true) ? $order->locale : 'nl',
            'status'            => $status,
            'can_plan'          => $status === 'ok',
            'pickup_date'       => $order->pickup_date?->toDateString(),
            'pickup_window'     => $order->pickup_window,
            'customer_postcode' => $order->customer_postcode,
            'customer_city'     => $order->customer_city,
            'depot_km'          => $depotKm,
            'slots'             => $slots,
        ];
    }

    /**
     * Het duurste moment dat wij deze klant mogen voorleggen: wat hij bij het
     * bestellen al voor ophalen heeft geaccepteerd.
     *
     * Die prijs stond in zijn orderbevestiging en komt zo op de factuur, dus een
     * duurder moment aanbieden zou betekenen dat wij achteraf meer rekenen dan
     * afgesproken. Een goedkoper moment mag altijd: pickup_cost blijft dan staan
     * en de klant betaalt niet meer dan hij al wist.
     */
    private function priceCeiling(Order $order): float
    {
        return (float) ($order->pickup_cost ?? 0);
    }

    /**
     * Waarom de klant wel of niet kan plannen.
     *
     * Een abonnement heeft een eigen ritme met vaste ophaaldagen, daar valt niets
     * los te kiezen. Staat er al een datum, dan is dit niet meer de plek: wijzigen
     * gaat via de herplanpagina, zodat er één plek is waar een bestaande afspraak
     * verandert.
     */
    private function status(Order $order): string
    {
        if ($order->isAbonnement()) {
            return 'subscription';
        }
        if (in_array($order->state, [Order::STATE_OPGEHAALD, Order::STATE_VERNIETIGD], true)) {
            return 'picked_up';
        }
        if ($order->state === Order::STATE_AFGESLOTEN) {
            return 'closed';
        }
        if ($order->pickup_date) {
            return 'already_planned';
        }

        return 'ok';
    }
}
