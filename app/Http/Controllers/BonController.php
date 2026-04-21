<?php

namespace App\Http\Controllers;

use App\Models\Bon;

class BonController extends Controller
{
    public function show(Bon $bon)
    {
        return view('bons.show', compact('bon'));
    }

    public function pdf(Bon $bon)
    {
        // TODO: Browsershot → PDF. For now stream the Blade as HTML.
        return view('bons.pdf', compact('bon'));
    }
}
