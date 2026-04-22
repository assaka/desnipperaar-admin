<?php

namespace App\Http\Controllers;

use App\Mail\CertificateIssued;
use App\Models\Certificate;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class CertificateController extends Controller
{
    public function show(Certificate $certificate)
    {
        $certificate->load(['order.bons.seals']);
        return view('certificates.show', compact('certificate'));
    }

    public function pdf(Certificate $certificate)
    {
        $certificate->load(['order.bons.seals']);
        return view('certificates.pdf', compact('certificate'));
    }

    public function generate(Request $request, Order $order)
    {
        if ($order->certificate) {
            return redirect()->route('certificates.show', $order->certificate);
        }

        $hasSignedBon = $order->bons()->whereNotNull('picked_up_at')->exists();
        if (!$hasSignedBon) {
            return back()->withErrors([
                'certificate' => 'Nog geen getekende bon — materiaal is nog niet opgehaald/afgeleverd.'
            ]);
        }

        $certificate = Certificate::create([
            'certificate_number' => Certificate::generateCertificateNumber(),
            'order_id'           => $order->id,
            'destroyed_at'       => now(),
            'destruction_method' => 'DIN 66399 H-4',
            'operator_name'      => $request->user()->name,
        ]);

        if ($order->state !== Order::STATE_AFGESLOTEN) {
            $order->update(['state' => Order::STATE_VERNIETIGD]);
        }

        return redirect()->route('certificates.show', $certificate);
    }

    public function mail(Request $request, Certificate $certificate)
    {
        $certificate->load('order');

        Mail::to($certificate->order->customer_email)
            ->send(new CertificateIssued($certificate, $request->user()));

        $certificate->update(['emailed_at' => now()]);
        $certificate->order->update(['state' => Order::STATE_AFGESLOTEN]);

        return back();
    }
}
