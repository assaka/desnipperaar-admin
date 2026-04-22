<?php

namespace App\Http\Controllers;

use App\Mail\BonSigned;
use App\Models\Invoice;
use App\Models\Bon;
use App\Models\Driver;
use App\Models\Seal;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class BonController extends Controller
{
    public function show(Bon $bon)
    {
        $bon->load(['order.customer', 'driver', 'seals']);
        $drivers = Driver::active()->orderBy('name')->get(['id','name','license_last4']);

        $order        = $bon->order;
        $mediaPrices  = ['hdd' => 9, 'ssd' => 15, 'usb' => 6, 'phone' => 12, 'laptop' => 19];
        $mediaLabels  = ['hdd' => 'HDD', 'ssd' => 'SSD / NVMe', 'usb' => 'USB / SD', 'phone' => 'Telefoon / tablet', 'laptop' => 'Laptop'];

        $buildQuote = function ($boxes, $containers, $media) use ($order, $mediaPrices, $mediaLabels) {
            $q = \App\Support\Pricing::quote((int) $boxes, (int) $containers, (bool) $order->pilot, (bool) $order->first_box_free);
            foreach ((array) $media as $k => $qty) {
                $qty = (int) $qty;
                if ($qty > 0 && isset($mediaPrices[$k])) {
                    $q['lines'][] = ['label' => $mediaLabels[$k], 'qty' => $qty, 'unit' => $mediaPrices[$k], 'subtotal' => $mediaPrices[$k] * $qty];
                }
            }
            $q['subtotal'] = round(array_sum(array_column($q['lines'], 'subtotal')), 2);
            $q['vat']      = round($q['subtotal'] * 0.21, 2);
            $q['total']    = round($q['subtotal'] + $q['vat'], 2);
            return $q;
        };

        $orderedQuote = $buildQuote($order->box_count, $order->container_count, $order->media_items ?? []);

        $actualBoxes = $bon->actual_boxes ?? $order->box_count;
        $actualCont  = $bon->actual_containers ?? $order->container_count;
        $actualMedia = $bon->actual_media ?? $order->media_items ?? [];

        $orderedMediaInt = array_map('intval', (array) ($order->media_items ?? []));
        $actualMediaInt  = array_map('intval', (array) $actualMedia);
        ksort($orderedMediaInt); ksort($actualMediaInt);

        $hasActualDiff = (int) $actualBoxes !== (int) $order->box_count
                      || (int) $actualCont !== (int) $order->container_count
                      || $orderedMediaInt !== $actualMediaInt;

        $actualQuote = $hasActualDiff ? $buildQuote($actualBoxes, $actualCont, $actualMedia) : null;

        $qrDataUri = $this->qrDataUri(URL::signedRoute('bons.public-pdf', ['bon' => $bon->id]));
        $publicPdfUrl = URL::signedRoute('bons.public-pdf', ['bon' => $bon->id]);

        return view('bons.show', compact('bon', 'drivers', 'orderedQuote', 'actualQuote', 'qrDataUri', 'publicPdfUrl'));
    }

    public function update(Request $request, Bon $bon)
    {
        // Lock: once bon is both picked up AND signed by customer, it becomes immutable.
        if ($bon->picked_up_at && $bon->customer_signature_path) {
            return redirect()->route('bons.show', $bon)
                ->withErrors(['locked' => 'Bon is al bevestigd en getekend — verdere wijzigingen niet toegestaan.']);
        }

        $data = $request->validate([
            'driver_id'           => 'nullable|exists:drivers,id',
            'picked_up_at'        => 'nullable|date',
            'weight_kg'           => 'nullable|numeric|min:0|max:10000',
            'notes'               => 'nullable|string|max:5000',
            'seals'               => 'nullable|string|max:5000',
            'customer_signature'  => 'nullable|string',
            'driver_signature'    => 'nullable|string',
            'actual_boxes'        => 'nullable|integer|min:0|max:500',
            'actual_containers'   => 'nullable|integer|min:0|max:50',
            'actual_media'        => 'nullable|array',
            'actual_media.*'      => 'nullable|integer|min:0|max:5000',
        ]);

        $hadSignatureBefore = !empty($bon->customer_signature_path);
        $hadPickupBefore    = !empty($bon->picked_up_at);

        $patch = [
            'picked_up_at'      => $data['picked_up_at']      ?? $bon->picked_up_at,
            'weight_kg'         => $data['weight_kg']         ?? $bon->weight_kg,
            'notes'             => $data['notes']             ?? $bon->notes,
            'actual_boxes'      => $data['actual_boxes']      ?? $bon->actual_boxes,
            'actual_containers' => $data['actual_containers'] ?? $bon->actual_containers,
        ];
        if (array_key_exists('actual_media', $data) && is_array($data['actual_media'])) {
            $patch['actual_media'] = array_filter($data['actual_media'], fn ($v) => (int) $v > 0);
        }

        if (!empty($data['driver_id']) && (int) $data['driver_id'] !== (int) $bon->driver_id) {
            $driver = Driver::find($data['driver_id']);
            $patch['driver_id']            = $driver->id;
            $patch['driver_name_snapshot'] = $driver->name;
            $patch['driver_license_last4'] = $driver->license_last4;
            // Pre-fill driver signature from his profile if he already has one and bon has none.
            if ($driver->signature_path && empty($bon->driver_signature_path)) {
                $copy = "signatures/bon-{$bon->id}-driver.png";
                \Illuminate\Support\Facades\Storage::disk('local')->put(
                    $copy,
                    \Illuminate\Support\Facades\Storage::disk('local')->get($driver->signature_path)
                );
                $patch['driver_signature_path'] = $copy;
            }
        }

        if (!empty($data['customer_signature']) && str_starts_with($data['customer_signature'], 'data:image/')) {
            $patch['customer_signature_path'] = $this->storeSignature($bon, 'customer', $data['customer_signature']);
            // Customer just signed — auto-fill picked_up_at if not already set.
            if (empty($bon->picked_up_at) && empty($patch['picked_up_at'])) {
                $patch['picked_up_at'] = now();
            }
        }
        if (!empty($data['driver_signature']) && str_starts_with($data['driver_signature'], 'data:image/')) {
            $patch['driver_signature_path'] = $this->storeSignature($bon, 'driver', $data['driver_signature']);
        }

        $bon->update($patch);
        // Order quantities are intentionally NOT synced — Order = frozen bestelling,
        // Bon.actual_* = frozen werkelijkheid. Invoice reads from bon.

        if (array_key_exists('seals', $data)) {
            $bon->seals()->delete();
            $lines = array_filter(array_map('trim', preg_split('/[\r\n,]+/', (string) $data['seals'])));
            foreach ($lines as $sealNumber) {
                try {
                    Seal::create([
                        'bon_id'         => $bon->id,
                        'seal_number'    => $sealNumber,
                        'container_type' => 'doos',
                    ]);
                } catch (\Illuminate\Database\QueryException $e) {
                    session()->flash('warning', "Zegel {$sealNumber} bestaat al elders.");
                }
            }
        }

        // Advance order state when pickup is finalized — triggers on explicit datetime OR on auto-fill at customer sign.
        if ($bon->picked_up_at && in_array($bon->order->state, [\App\Models\Order::STATE_NIEUW, \App\Models\Order::STATE_BEVESTIGD])) {
            $bon->order->update(['state' => \App\Models\Order::STATE_OPGEHAALD]);
        }

        // Mail signed bon to customer — only once, when signature + pickup are both present.
        $hasSignatureNow = !empty($bon->fresh()->customer_signature_path);
        $hasPickupNow    = !empty($bon->fresh()->picked_up_at);
        $justSigned      = $hasSignatureNow && $hasPickupNow && (!$hadSignatureBefore || !$hadPickupBefore);

        if ($justSigned) {
            try {
                Mail::to($bon->order->customer_email)
                    ->send(new BonSigned($bon->fresh()->load(['order.customer', 'driver', 'seals']), $request->user()));
                session()->flash('status', "Getekende bon per e-mail verstuurd naar {$bon->order->customer_email}.");
            } catch (\Throwable $e) {
                report($e);
                session()->flash('warning', 'Bon opgeslagen maar mail kon niet worden verstuurd: ' . $e->getMessage());
            }

            // Auto-generate + auto-send invoice from signed bon's actuals.
            if (!Invoice::where('bon_id', $bon->id)->exists()) {
                try {
                    $invoice = Invoice::fromBon($bon->fresh());
                    Mail::to($invoice->customer_email)
                        ->send(new \App\Mail\InvoiceSent($invoice, $request->user()));
                    $invoice->update(['status' => Invoice::STATUS_SENT, 'sent_at' => now()]);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        return redirect()->route('bons.show', $bon);
    }

    public function pdf(Bon $bon)
    {
        $bon->load(['order.customer', 'driver', 'seals']);
        [$customerSigDataUri, $driverSigDataUri] = $this->signatureDataUris($bon);
        return view('bons.pdf', compact('bon', 'customerSigDataUri', 'driverSigDataUri'));
    }

    public function publicPdf(Bon $bon)
    {
        $bon->load(['order.customer', 'driver', 'seals']);
        [$customerSigDataUri, $driverSigDataUri] = $this->signatureDataUris($bon);
        $pdf = Pdf::loadView('bons.pdf', compact('bon', 'customerSigDataUri', 'driverSigDataUri'))->setPaper('a4');
        return $pdf->download("bon-{$bon->bon_number}.pdf");
    }

    private function signatureDataUris(Bon $bon): array
    {
        $toDataUri = function (?string $path): ?string {
            if (!$path || !Storage::disk('local')->exists($path)) return null;
            return 'data:image/png;base64,' . base64_encode(Storage::disk('local')->get($path));
        };
        return [
            $toDataUri($bon->customer_signature_path),
            $toDataUri($bon->driver_signature_path),
        ];
    }

    private function qrDataUri(string $payload): string
    {
        $result = (new Builder(
            writer: new SvgWriter(),
            writerOptions: [],
            validateResult: false,
            data: $payload,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 200,
            margin: 4,
        ))->build();
        return 'data:' . $result->getMimeType() . ';base64,' . base64_encode($result->getString());
    }

    public function signature(Bon $bon, string $role)
    {
        $path = $role === 'customer' ? $bon->customer_signature_path : $bon->driver_signature_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);
        return response(Storage::disk('local')->get($path), 200, ['Content-Type' => 'image/png']);
    }

    private function storeSignature(Bon $bon, string $role, string $dataUri): string
    {
        $base64 = preg_replace('#^data:image/\w+;base64,#', '', $dataUri);
        $binary = base64_decode($base64);
        $path   = "signatures/bon-{$bon->id}-{$role}.png";
        Storage::disk('local')->put($path, $binary);
        return $path;
    }
}
