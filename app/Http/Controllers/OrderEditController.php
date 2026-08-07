<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\Pricing;
use Illuminate\Http\Request;

/**
 * Adres en inhoud van een bestaande order bijwerken.
 *
 * Tot nu toe kon een order na het aanmaken niet meer worden aangepast: een
 * verkeerd doorgegeven postcode of een klant die belt dat het toch zes dozen
 * zijn, betekende een nieuwe order aanmaken.
 *
 * Twee dingen bewegen mee met het adres, omdat ze eruit volgen:
 *  - de pilotkorting hangt aan de postcode, dus die wordt opnieuw bepaald;
 *  - lat/lon en geocoded_at worden gewist, zodat de planning het nieuwe adres
 *    opzoekt. Geocoder::forOrder() gaat er expliciet van uit dat de orderpagina
 *    dat doet zodra het adres wijzigt.
 *
 * Wat bewust niet meebeweegt: pickup_cost en pickup_km. Die komen uit de
 * afstandsberekening op de publieke server en zijn met de klant afgesproken;
 * die stilletjes herrekenen zou de prijs veranderen zonder dat iemand het ziet.
 * Bij een gewijzigde postcode wordt er daarom op gewezen.
 */
class OrderEditController extends Controller
{
    public function update(Request $request, Order $order)
    {
        $rules = [
            'customer_name'     => 'required|string|max:150',
            'customer_email'    => 'required|email|max:190',
            'customer_phone'    => 'nullable|string|max:40',
            'customer_address'  => 'required|string|max:200',
            'customer_postcode' => 'required|string|max:12',
            'customer_city'     => 'required|string|max:100',
            'box_count'         => 'required|integer|min:0|max:500',
            'container_count'   => 'required|integer|min:0|max:50',
            'media'             => 'nullable|array',
            'notify'            => 'nullable|boolean',
        ];
        foreach (array_keys(Pricing::MEDIA_TIERS) as $key) {
            $rules["media.$key"] = 'nullable|integer|min:0|max:10000';
        }
        $data = $request->validate($rules);

        // Alleen dragers met een aantal bewaren, zodat er geen rij met 0 in de
        // factuurregels of de mail opduikt.
        $media = [];
        foreach (array_keys(Pricing::MEDIA_TIERS) as $key) {
            $qty = (int) ($data['media'][$key] ?? 0);
            if ($qty > 0) {
                $media[$key] = $qty;
            }
        }

        if ($data['box_count'] < 1 && $data['container_count'] < 1 && $media === []) {
            return back()->withErrors([
                'box_count' => 'Een order zonder dozen, containers en dragers heeft niets om op te halen.',
            ])->withInput();
        }

        $oldPostcode = preg_replace('/\s+/', '', (string) $order->customer_postcode);
        $newPostcode = preg_replace('/\s+/', '', $data['customer_postcode']);
        $moved       = strcasecmp($oldPostcode, $newPostcode) !== 0;

        $attributes = [
            'customer_name'     => $data['customer_name'],
            'customer_email'    => $data['customer_email'],
            'customer_phone'    => $data['customer_phone'] ?? null,
            'customer_address'  => $data['customer_address'],
            'customer_postcode' => $data['customer_postcode'],
            'customer_city'     => $data['customer_city'],
            'box_count'         => $data['box_count'],
            'container_count'   => $data['container_count'],
            'media_items'       => $media,
        ];

        $notes = [];

        if ($moved) {
            // Opnieuw opzoeken op het nieuwe adres.
            $attributes['lat']         = null;
            $attributes['lon']         = null;
            $attributes['geocoded_at'] = null;

            $pilot = Pricing::isPilotPostcode($data['customer_postcode']);
            if ((bool) $order->pilot !== $pilot) {
                $attributes['pilot'] = $pilot;
                $notes[] = $pilot
                    ? 'postcode valt nu in de Amsterdam-pilot, de pilotkorting staat aan'
                    : 'postcode valt buiten de Amsterdam-pilot, de pilotkorting staat uit';
            }

            if ((float) ($order->pickup_cost ?? 0) > 0) {
                $notes[] = 'let op: de ophaalkosten van € '
                    .number_format((float) $order->pickup_cost, 2, ',', '.')
                    .' zijn nog van het oude adres en worden niet automatisch herrekend';
            }
        }

        $order->update($attributes);

        // Staat er een korting op, dan is de grondslag mogelijk veranderd. Het
        // bedrag blijft staan zoals het is toegezegd; alleen de factuurregels
        // worden opnieuw gezet zodat de factuur bij de nieuwe inhoud past.
        foreach ($order->invoices()->get() as $invoice) {
            if ($invoice->isCreditNote()
                || !in_array($invoice->status, [\App\Models\Invoice::STATUS_DRAFT, \App\Models\Invoice::STATUS_SENT], true)) {
                continue;
            }
            $invoice->setRelation('order', $order);
            $invoice->syncCouponLine();
        }

        if ($order->isPickedUp()) {
            $notes[] = 'deze order is al opgehaald, de factuur volgt de aantallen van de bon en niet deze';
        }

        // Alleen mailen als er echt iets veranderd is. Een bevestiging die niets
        // nieuws bevat verwart de klant en zet hem aan het zoeken naar het
        // verschil, en er is er al een de deur uit.
        $changed = $order->wasChanged();

        if ($request->boolean('notify')) {
            if (! $changed) {
                $notes[] = 'er was niets gewijzigd, dus er is geen bevestiging gestuurd';
            } else {
                try {
                    \Illuminate\Support\Facades\Mail::to($order->customer_email)
                        ->send(new \App\Mail\OrderCreated($order->fresh()));
                    $notes[] = 'bijgewerkte bevestiging gestuurd naar '.$order->customer_email;
                } catch (\Throwable $e) {
                    report($e);
                    $notes[] = 'de bevestiging kon niet worden verstuurd ('.$e->getMessage().')';
                }
            }
        } elseif ($changed) {
            $notes[] = 'de klant heeft nog de oude gegevens, stuur zo nodig de bevestiging opnieuw';
        }

        return back()->with('status', 'Order '.$order->order_number
            .($changed ? ' bijgewerkt.' : ' ongewijzigd.')
            .($notes ? ' '.ucfirst(implode('. ', $notes)).'.' : ''));
    }
}
