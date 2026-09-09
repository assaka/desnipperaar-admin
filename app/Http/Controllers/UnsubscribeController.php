<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;
use Illuminate\Http\Request;

class UnsubscribeController extends Controller
{
    public function show(Request $request, string $token)
    {
        $subscriber = $this->optOut($token);

        // Prefer the language carried on the link (?lang=) so even an unknown or
        // already-removed token still renders the page in the reader's language.
        // Fall back to the stored subscriber language, then Dutch.
        $lang = $request->query('lang');
        if (! in_array($lang, ['nl', 'en', 'fr', 'es'], true)) {
            $lang = in_array($subscriber?->lang, ['nl', 'en', 'fr', 'es'], true) ? $subscriber->lang : 'nl';
        }

        return view('unsubscribe', ['lang' => $lang, 'found' => (bool) $subscriber]);
    }

    /**
     * RFC 8058 one-click target for the List-Unsubscribe-Post header. Mailbox
     * providers POST here on the reader's behalf and expect a bare 2xx, never
     * a page. Always answers 204, also for an unknown or already-removed
     * token, so a provider never records the opt-out as failed.
     */
    public function oneClick(string $token)
    {
        $this->optOut($token);

        return response()->noContent();
    }

    private function optOut(string $token): ?Subscriber
    {
        $subscriber = Subscriber::where('unsubscribe_token', $token)->first();

        if ($subscriber && ! $subscriber->unsubscribed_at) {
            $subscriber->update(['unsubscribed_at' => now()]);
        }

        return $subscriber;
    }
}
