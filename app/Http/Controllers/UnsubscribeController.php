<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;

class UnsubscribeController extends Controller
{
    public function show(string $token)
    {
        $subscriber = Subscriber::where('unsubscribe_token', $token)->first();

        if ($subscriber && ! $subscriber->unsubscribed_at) {
            $subscriber->update(['unsubscribed_at' => now()]);
        }

        $lang = in_array($subscriber?->lang, ['nl', 'en', 'fr', 'es'], true) ? $subscriber->lang : 'nl';

        return view('unsubscribe', ['lang' => $lang, 'found' => (bool) $subscriber]);
    }
}
