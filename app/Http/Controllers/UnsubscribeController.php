<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;
use Illuminate\Http\Request;

class UnsubscribeController extends Controller
{
    /**
     * The link in the SnipperDag e-mails. This is deliberately a read-only
     * page: mail scanners and link previewers (Outlook SafeLinks and company
     * gateways) fetch every URL in a message, so opting out on a plain GET
     * silently removed subscribers who never clicked anything. The page asks
     * for a confirmation and the button POSTs to unsubscribe() below.
     */
    public function show(Request $request, string $token)
    {
        $subscriber = Subscriber::where('unsubscribe_token', $token)->first();

        if (! $subscriber) {
            $state = 'invalid';
        } elseif ($subscriber->unsubscribed_at) {
            $state = 'done';
        } else {
            $state = 'confirm';
        }

        return view('unsubscribe', [
            'lang'  => $this->lang($request, $subscriber),
            'state' => $state,
            'token' => $token,
        ]);
    }

    /**
     * Performs the opt-out. Two very different callers land here.
     *
     * A mailbox provider acting on the reader's behalf via the
     * List-Unsubscribe-Post header (RFC 8058) expects a bare 2xx and no page,
     * and must never be shown a confirmation step. The button on the page
     * above sends `confirm`, which no provider does, so that is what tells the
     * two apart. Answers 204 for an unknown or already-removed token as well,
     * so a provider never records the opt-out as failed.
     */
    public function unsubscribe(Request $request, string $token)
    {
        $subscriber = Subscriber::where('unsubscribe_token', $token)->first();

        if ($subscriber && ! $subscriber->unsubscribed_at) {
            $subscriber->update(['unsubscribed_at' => now()]);
        }

        if (! $request->has('confirm')) {
            return response()->noContent();
        }

        return view('unsubscribe', [
            'lang'  => $this->lang($request, $subscriber),
            'state' => $subscriber ? 'done' : 'invalid',
            'token' => $token,
        ]);
    }

    /**
     * Prefer the language carried on the link (?lang=) so even an unknown or
     * already-removed token still renders the page in the reader's language.
     * Falls back to the stored subscriber language, then Dutch.
     */
    private function lang(Request $request, ?Subscriber $subscriber): string
    {
        $lang = $request->input('lang');
        if (in_array($lang, ['nl', 'en', 'fr', 'es'], true)) {
            return $lang;
        }

        return in_array($subscriber?->lang, ['nl', 'en', 'fr', 'es'], true) ? $subscriber->lang : 'nl';
    }
}
