<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscribeController extends Controller
{
    /**
     * Capture a DeSnipperaar Dag e-mail signup from the public site
     * (exit-intent popup or the homepage inline section).
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'        => 'required|email|max:255',
            'lang'         => 'nullable|in:nl,en,fr,es',
            'source'       => 'nullable|string|max:50',
            'gclid'        => 'nullable|string|max:255',
            'utm_source'   => 'nullable|string|max:255',
            'utm_medium'   => 'nullable|string|max:255',
            'utm_campaign' => 'nullable|string|max:255',
            'utm_term'     => 'nullable|string|max:255',
            'utm_content'  => 'nullable|string|max:255',
            'landing_page' => 'nullable|string|max:255',
        ]);

        $email = strtolower(trim($data['email']));

        $subscriber = Subscriber::firstOrNew(['email' => $email]);

        // Always refresh language + source; fill acquisition fields only when
        // present so a later submit never wipes the first-touch attribution.
        $subscriber->lang   = $data['lang'] ?? $subscriber->lang ?? 'nl';
        $subscriber->source = $data['source'] ?? $subscriber->source;

        foreach (['gclid', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'landing_page'] as $f) {
            if (! empty($data[$f]) && empty($subscriber->{$f})) {
                $subscriber->{$f} = $data[$f];
            }
        }

        if (! $subscriber->exists) {
            $subscriber->ip = $request->ip();
        }

        // A returning subscriber who had opted out is re-opted-in by signing up again.
        $subscriber->unsubscribed_at = null;
        $subscriber->save();

        return response()->json(['ok' => true], 201);
    }
}
