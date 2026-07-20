<?php

namespace App\Http\Controllers;

use App\Mail\InvoiceSent;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('status'));
        $invoices = Invoice::query()
            ->when($q, fn ($qb) => $qb->where('status', $q))
            ->orderByDesc('id')
            ->paginate(25);
        return view('invoices.index', compact('invoices', 'q'));
    }

    public function show(Invoice $invoice)
    {
        return view('invoices.show', compact('invoice'));
    }

    public function pdf(Invoice $invoice)
    {
        $pdf = Pdf::loadView('invoices.pdf', ['invoice' => $invoice])->setPaper('a4');
        return $pdf->stream("factuur-{$invoice->invoice_number}.pdf");
    }

    public function mail(Request $request, Invoice $invoice)
    {
        Mail::to($invoice->customer_email)->send(new InvoiceSent($invoice, $request->user()));
        $invoice->update([
            'status'  => Invoice::STATUS_SENT,
            'sent_at' => now(),
        ]);
        return back()->with('status', "Factuur {$invoice->invoice_number} verzonden naar {$invoice->customer_email}.");
    }

    /**
     * Boek een factuur tegen met een creditfactuur.
     *
     * Bedoeld voor het geval dat wij door onze eigen schuld niet zijn langsgeweest;
     * dat is op de site en in de activatiemail beloofd. Het origineel blijft staan
     * en de creditfactuur draait de bedragen om. De creditfactuur komt als concept
     * binnen, zodat er nog naar gekeken wordt voordat hij naar de klant gaat.
     */
    public function credit(Request $request, Invoice $invoice)
    {
        abort_if($invoice->isCreditNote(), 422, 'Een creditfactuur kan niet zelf gecrediteerd worden.');
        abort_if($invoice->isCredited(), 422, 'Deze factuur is al gecrediteerd.');

        $data = $request->validate([
            'reason' => 'nullable|string|max:300',
        ]);

        $credit = $invoice->createCreditNote($data['reason'] ?? null);

        return redirect()->route('invoices.show', $credit)->with(
            'status',
            "Creditfactuur {$credit->invoice_number} aangemaakt voor {$invoice->invoice_number}. "
            .'Deze staat als concept klaar, verstuur hem hieronder naar de klant.'
        );
    }

    public function markPaid(Invoice $invoice)
    {
        $invoice->update([
            'status'  => Invoice::STATUS_PAID,
            'paid_at' => now(),
        ]);
        return back()->with('status', "Factuur {$invoice->invoice_number} gemarkeerd als betaald.");
    }
}
