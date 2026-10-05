<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

/**
 * De knop in de ophaalmail waarmee de klant bevestigt dat hij hem ontvangen
 * heeft. Op de orderlijst staat dan een vinkje achter de status.
 *
 * Net als bij afmelden is de link zelf alleen een pagina. Mailscanners
 * (Outlook SafeLinks, bedrijfsgateways) openen elke URL in een bericht, dus een
 * bevestiging op een kale GET zou orders afvinken waar niemand naar keek. De
 * knop op de pagina POST naar confirm().
 *
 * De pagina toont het moment zoals het nu staat. Is de ophaling intussen
 * verzet, dan bevestigt de klant het nieuwe moment, en dat is ook wat hij ziet.
 */
class PickupReceiptController extends Controller
{
    public function show(Request $request, string $token)
    {
        $order = $this->order($token);

        return view('pickup-receipt', [
            'lang'  => $this->lang($request, $order),
            'state' => $this->state($order),
            'order' => $order,
            'token' => $token,
        ]);
    }

    public function confirm(Request $request, string $token)
    {
        $order = $this->order($token);

        if ($this->state($order) === 'confirm') {
            $order->update([
                'pickup_receipt_confirmed_at' => now(),
                'pickup_receipt_moment'       => $order->pickupMoment(),
            ]);
        }

        return view('pickup-receipt', [
            'lang'  => $this->lang($request, $order),
            'state' => $order && $order->pickupReceiptConfirmed() ? 'done' : 'invalid',
            'order' => $order,
            'token' => $token,
        ]);
    }

    private function order(string $token): ?Order
    {
        return Order::where('public_token', $token)->first();
    }

    private function state(?Order $order): string
    {
        if ($order && $order->pickupReceiptConfirmed()) {
            return 'done';
        }

        if (! $order || ! $order->pickup_date || $order->isCanceled() || $order->isPickedUp()) {
            return 'invalid';
        }

        return 'confirm';
    }

    private function lang(Request $request, ?Order $order): string
    {
        $lang = $request->input('lang');
        if (in_array($lang, ['nl', 'en', 'fr', 'es'], true)) {
            return $lang;
        }

        return in_array($order?->locale, ['nl', 'en', 'fr', 'es'], true) ? $order->locale : 'nl';
    }
}
