<?php

namespace App\Http\Controllers;

use App\Models\Certificate;

class CertificateController extends Controller
{
    public function show(Certificate $certificate)
    {
        return view('certificates.show', compact('certificate'));
    }

    public function pdf(Certificate $certificate)
    {
        return view('certificates.pdf', compact('certificate'));
    }
}
