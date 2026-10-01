<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RouteRun;
use Illuminate\Http\Request;

/**
 * Past deze postcode op een open rit? Gevraagd door /order zodra de klant
 * buiten de gratis straal blijkt te wonen.
 *
 * Het antwoord zegt alleen ja of nee en de datum. Geen bestemming, geen omweg
 * en niets over wie er verder meerijdt: dat is de adresgegevens van een andere
 * klant, en hoe wij rijden is onze zaak en niet die van de bezoeker.
 */
class RouteRunController extends Controller
{
    public function match(Request $request)
    {
        $postcode = (string) $request->query('postcode', '');

        if (! preg_match('/^\d{4}\s?[A-Za-z]{2}$/', $postcode)) {
            return response()->json(['match' => false]);
        }

        $hit = RouteRun::matchFor($postcode);

        return response()->json($hit
            ? ['match' => true, 'date' => $hit['run']->run_date?->toDateString()]
            : ['match' => false]);
    }
}
