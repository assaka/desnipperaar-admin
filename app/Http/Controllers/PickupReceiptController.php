<?php

namespace App\Http\Controllers;

use App\Mail\PickupFinalized;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * De knop "Ontvangen" in de ophaalmail. De klant bevestigt dat de mail met het
 * ophaalmoment is aangekomen; op de orderlijst staat dan een vinkje achter de
 * status. Komt het moment niet uit, dan stuurt de klant ons een WhatsApp. Dat
 * loopt bewust niet via deze pagina: verzetten doen wij, in gesprek.
 *
 * Net als bij afmelden is de link zelf alleen een pagina. Mailscanners
 * (Outlook SafeLinks, bedrijfsgateways) openen elke URL in een bericht, dus een
 * bevestiging op een kale GET zou orders afvinken waar niemand naar keek. De
 * knop op de pagina POST naar confirm().
 *
 * Alle ophaalmails van een order delen dezelfde token, dus de link draagt ook
 * het moment waarover die mail ging (?m=). Klikt de klant in een oudere mail
 * terwijl de ophaling intussen verzet is, dan zegt de pagina dat en toont hij
 * het actuele moment. De bevestiging geldt altijd het moment dat de klant op de
 * pagina ziet.
 */
class PickupReceiptController extends Controller
{
    public function show(Request $request, string $token)
    {
        $order = $this->order($token);

        return $this->page($request, $order, $token);
    }

    public function confirm(Request $request, string $token)
    {
        $order = $this->order($token);

        if ($this->state($order) === 'confirm') {
            $order->update([
                'pickup_receipt_confirmed_at' => now(),
                'pickup_receipt_moment'       => $order->pickupMoment(),
                'pickup_receipt_answer'       => Order::RECEIPT_AKKOORD,
            ]);

            // Alleen bij de eerste klik voor dit moment, dus een tweede klik of
            // een herladen pagina stuurt niet nog een mail.
            try {
                Mail::to($order->customer_email)->send(new PickupFinalized($order->fresh()->load('customer')));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $this->page($request, $order, $token);
    }

    private function page(Request $request, ?Order $order, string $token)
    {
        $state = $this->state($order);
        $linkMoment = (string) $request->input('m');

        return view('pickup-receipt', [
            'lang'     => $this->lang($request, $order),
            'state'    => $state,
            'order'    => $order,
            'token'    => $token,
            'm'        => $linkMoment,
            // De mail waaruit de klant kwam ging over een ander moment dan er nu
            // staat. Links zonder m (de eerste mails met deze knop) slaan dit over.
            'gewijzigd' => $state === 'confirm' && $linkMoment !== '' && $linkMoment !== $order->pickupMoment(),
        ]);
    }

    private function order(string $token): ?Order
    {
        return Order::where('public_token', $token)->first();
    }

    /** confirm, done of invalid. */
    private function state(?Order $order): string
    {
        if ($order && $order->pickupReceiptAnswer()) {
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
