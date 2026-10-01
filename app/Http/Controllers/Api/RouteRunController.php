<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RouteRun;
use Illuminate\Http\Request;

/**
 * Past deze postcode op een open rit? Gevraagd door /order zodra de klant
 * buiten de gratis straal blijkt te wonen.
 *
 * Het antwoord zegt alleen ja of nee. Geen datum, want die spreken wij met de
 * klant zelf af. Geen bestemming, geen omweg en niets over wie er verder
 * meerijdt: dat zijn adresgegevens van een andere klant.
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

        return response()->json(['match' => $hit !== null]);
    }
}
