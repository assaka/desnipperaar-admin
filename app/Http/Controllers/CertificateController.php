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
        $certificate->load(['order.bons.seals', 'bon.seals']);
        return view('certificates.show', compact('certificate'));
    }

    public function pdf(Certificate $certificate)
    {
        $certificate->load(['order.bons.seals', 'bon.seals']);
        return view('certificates.pdf', compact('certificate'));
    }

    public function generate(Request $request, Order $order)
    {

        // Bij een bezorging is er niets opgehaald en dus niets vernietigd. Een
        // vernietigingscertificaat daarvoor zou verklaren dat materiaal is
        // vernietigd dat nooit is meegenomen.
        // Een certificaat hoort bij één vernietiging, dus bij één getekende bon.
        // Bij een abonnement zijn dat er meerdere op dezelfde order. Bezorg- en
        // retourritten leveren er geen op: daar wordt niets meegenomen.
        $bon = $order->bons()
            ->whereNotNull('picked_up_at')
            ->whereNotIn('mode', \App\Models\Bon::MODES_ZONDER_VERNIETIGING)
            ->whereDoesntHave('certificate')
            ->orderBy('picked_up_at')
            ->first();

        $hasSignedBon = $bon !== null;
        if (!$hasSignedBon) {
            return back()->withErrors([
                'certificate' => 'Nog geen getekende bon — materiaal is nog niet opgehaald/afgeleverd.'
            ]);
        }

        $certificate = Certificate::create([
            'certificate_number' => Certificate::generateCertificateNumber(),
            'order_id'           => $order->id,
            'bon_id'             => $bon->id,
            'destroyed_at'       => now(),
            'destruction_method' => 'DIN 66399 P-5 / H-4',
            'operator_name'      => $request->user()->name,
        ]);

        // Auto-send — admin can resend from the show page if it fails.
        try {
            Mail::to($order->customer_email)
                ->send(new CertificateIssued($certificate->fresh()->load('order'), $request->user()));
            $certificate->update(['emailed_at' => now()]);
            $order->update(['state' => Order::STATE_AFGESLOTEN]);
            return redirect()->route('certificates.show', $certificate)
                ->with('status', "Certificaat {$certificate->certificate_number} gemaakt en verzonden naar {$order->customer_email}.");
        } catch (\Throwable $e) {
            report($e);
            if ($order->state !== Order::STATE_AFGESLOTEN) {
                $order->update(['state' => Order::STATE_VERNIETIGD]);
            }
            return redirect()->route('certificates.show', $certificate)
                ->withErrors(['mail' => 'Certificaat gemaakt maar mail kon niet worden verstuurd: ' . $e->getMessage()]);
        }
    }

    /**
     * Corrigeer wat het certificaat verklaart: locatie en hoeveelheden. De
     * getekende bon blijft zoals hij is, het certificaat is de verklaring over
     * de vernietiging zelf. Na een correctie moet het opnieuw naar de klant.
     */
    public function correct(Request $request, Certificate $certificate)
    {
        $data = $request->validate([
            'destruction_location' => 'required|in:' . Certificate::LOC_DEPOT . ',' . Certificate::LOC_ONSITE,
            'boxes_final'          => 'nullable|integer|min:0|max:5000',
            'containers_final'     => 'nullable|integer|min:0|max:500',
            'weight_kg_final'      => 'nullable|numeric|min:0|max:100000',
        ]);

        $certificate->update($data);

        return back()->with('status', 'Certificaat gecorrigeerd. Mail het opnieuw naar de klant.');
    }

    public function mail(Request $request, Certificate $certificate)
    {
        $certificate->load('order');

        // Ging hij al eerder de deur uit, dan is dit een correctie.
        Mail::to($certificate->order->customer_email)
            ->send(new CertificateIssued($certificate, $request->user(), corrected: (bool) $certificate->emailed_at));

        $certificate->update(['emailed_at' => now()]);
        $certificate->order->update(['state' => Order::STATE_AFGESLOTEN]);

        return back();
    }
}
