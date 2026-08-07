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

    /**
     * Verstuur de factuur, standaard naar de klant.
     *
     * Met een afwijkend `to` is het een proefzending: dan blijven status en
     * sent_at staan, want de klant heeft hem nog niet gehad en de factuur zou
     * anders als verstuurd in de boeken staan zonder dat dat waar is. Zelfde
     * patroon als de proefzending bij orders.mail.
     */
    public function mail(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'to' => 'nullable|email',
        ]);
        $to      = $data['to'] ?? $invoice->customer_email;
        $isProef = strcasecmp($to, (string) $invoice->customer_email) !== 0;

        try {
            Mail::to($to)->send(new InvoiceSent($invoice, $request->user()));
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['mail' => 'Kon factuur niet versturen: '.$e->getMessage()]);
        }

        if ($isProef) {
            return back()->with('status', "Proefzending van factuur {$invoice->invoice_number} naar {$to}. Status ongewijzigd.");
        }

        // Een concept wordt verstuurd, al het andere houdt zijn status. Anders zou
        // een klant die om een kopie vraagt een betaalde factuur terugzetten naar
        // verstuurd, en dan lijkt hij weer open te staan.
        $invoice->update([
            'status'  => $invoice->status === Invoice::STATUS_DRAFT ? Invoice::STATUS_SENT : $invoice->status,
            'sent_at' => now(),
        ]);

        return back()->with('status', "Factuur {$invoice->invoice_number} verzonden naar {$to}.");
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

        // Alleen een betaalde factuur. Staat hij nog open, dan is er niets terug te
        // boeken: dan pas je de order aan en wordt de factuur herrekend. Een
        // creditfactuur op een onbetaalde factuur zet twee stukken in de boeken die
        // elkaar opheffen zonder dat er ooit geld is geweest.
        abort_unless(
            $invoice->status === Invoice::STATUS_PAID,
            422,
            "Factuur {$invoice->invoice_number} is niet betaald. Pas de order aan in plaats van te crediteren."
        );

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
