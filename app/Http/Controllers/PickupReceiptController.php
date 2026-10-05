<?php

namespace App\Http\Controllers;

use App\Mail\PickupReceiptDeclined;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * De vraag in de ophaalmail: past dit moment? De klant antwoordt met "ja" of
 * "past niet". Op de orderlijst staat dan een vinkje of een kruisje achter de
 * status, en bij "past niet" krijgen wij een mail met wat de klant schreef.
 *
 * Net als bij afmelden is de link zelf alleen een pagina. Mailscanners
 * (Outlook SafeLinks, bedrijfsgateways) openen elke URL in een bericht, dus een
 * antwoord op een kale GET zou orders afvinken waar niemand naar keek. De knop
 * op de pagina POST naar answer().
 *
 * De pagina toont het moment zoals het nu staat. Is de ophaling intussen
 * verzet, dan gaat het antwoord over het nieuwe moment, en dat is ook wat de
 * klant ziet.
 */
class PickupReceiptController extends Controller
{
    public function show(Request $request, string $token)
    {
        $order = $this->order($token);

        return $this->page($request, $order, $token, $this->state($order));
    }

    public function answer(Request $request, string $token)
    {
        $order = $this->order($token);
        $answer = $request->input('antwoord');

        if ($this->state($order) === 'confirm'
            && in_array($answer, [Order::RECEIPT_AKKOORD, Order::RECEIPT_PAST_NIET], true)) {
            $note = trim((string) $request->input('note')) ?: null;

            $order->update([
                'pickup_receipt_confirmed_at' => now(),
                'pickup_receipt_moment'       => $order->pickupMoment(),
                'pickup_receipt_answer'       => $answer,
                'pickup_receipt_note'         => $answer === Order::RECEIPT_PAST_NIET ? mb_substr((string) $note, 0, 2000) ?: null : null,
            ]);

            $adminEmail = config('desnipperaar.notifications.admin_email');
            if ($answer === Order::RECEIPT_PAST_NIET && $adminEmail) {
                try {
                    Mail::to($adminEmail)->send(new PickupReceiptDeclined($order->fresh()->load('customer')));
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        return $this->page($request, $order, $token, $this->state($order));
    }

    private function page(Request $request, ?Order $order, string $token, string $state)
    {
        return view('pickup-receipt', [
            'lang'    => $this->lang($request, $order),
            'state'   => $state,
            'order'   => $order,
            'token'   => $token,
            // Welke knop de klant in de mail aanklikte, zodat de pagina daarmee
            // opent. Het is alleen een voorkeur, de klant kan nog wisselen.
            'gekozen' => $request->query('antwoord') === 'nee' ? Order::RECEIPT_PAST_NIET : Order::RECEIPT_AKKOORD,
        ]);
    }

    private function order(string $token): ?Order
    {
        return Order::where('public_token', $token)->first();
    }

    /** confirm, akkoord, past_niet of invalid. */
    private function state(?Order $order): string
    {
        if ($order && ($answer = $order->pickupReceiptAnswer())) {
            return $answer;
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
