<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;
use Illuminate\Http\Request;

class UnsubscribeController extends Controller
{
    public function show(Request $request, string $token)
    {
        $subscriber = Subscriber::where('unsubscribe_token', $token)->first();

        if ($subscriber && ! $subscriber->unsubscribed_at) {
            $subscriber->update(['unsubscribed_at' => now()]);
        }

        // Prefer the language carried on the link (?lang=) so even an unknown or
        // already-removed token still renders the page in the reader's language.
        // Fall back to the stored subscriber language, then Dutch.
        $lang = $request->query('lang');
        if (! in_array($lang, ['nl', 'en', 'fr', 'es'], true)) {
            $lang = in_array($subscriber?->lang, ['nl', 'en', 'fr', 'es'], true) ? $subscriber->lang : 'nl';
        }

        return view('unsubscribe', ['lang' => $lang, 'found' => (bool) $subscriber]);
    }
}
