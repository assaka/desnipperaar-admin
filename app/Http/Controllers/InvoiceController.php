<?php

namespace App\Http\Controllers;

use App\Mail\InvoiceSent;
use App\Mail\PaymentReceived;
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
        // Op onze naam en niet op soort document: in een map met downloads zegt
        // "desnipperaar-F-2026-0170" wie het gestuurd heeft, "factuur-..." niet.
        return $pdf->stream("desnipperaar-{$invoice->invoice_number}.pdf");
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
     * en de creditfactuur draait de bedragen om. Hij krijgt status 'credit' en
     * houdt die ook na het versturen: er valt niets te innen en niets te vervallen.
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
            .'Verstuur hem hieronder naar de klant.'
        );
    }

    public function markPaid(Request $request, Invoice $invoice)
    {
        // Was hij al betaald, dan is dit een dubbele klik of een tweede tabblad.
        // Niet opnieuw stempelen en niet opnieuw mailen: de klant heeft het gehoord.
        if ($invoice->status === Invoice::STATUS_PAID) {
            return back()->with('status', "Factuur {$invoice->invoice_number} stond al als betaald.");
        }

        $invoice->update([
            'status'  => Invoice::STATUS_PAID,
            'paid_at' => now(),
        ]);

        // De betaling afvinken is voor ons administratie, voor de klant het einde
        // van de order. Vandaar een bevestiging dat het geld binnen is.
        try {
            Mail::to($invoice->customer_email)
                ->send(new PaymentReceived($invoice->fresh(), $request->user()));
        } catch (\Throwable $e) {
            report($e);

            return back()->with('status', "Factuur {$invoice->invoice_number} gemarkeerd als betaald.")
                ->withErrors(['mail' => 'De betaalbevestiging kon niet worden verstuurd: '.$e->getMessage()]);
        }

        return back()->with('status', "Factuur {$invoice->invoice_number} gemarkeerd als betaald. "
            ."Betaalbevestiging gestuurd naar {$invoice->customer_email}.");
    }
}
