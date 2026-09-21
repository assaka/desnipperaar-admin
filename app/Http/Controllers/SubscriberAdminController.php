<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubscriberAdminController extends Controller
{
    public function index()
    {
        $subscribers = Subscriber::orderByDesc('created_at')->paginate(50);
        $total       = Subscriber::active()->count();
        return view('subscribers.index', compact('subscribers', 'total'));
    }

    /**
     * Handmatig afmelden, bijvoorbeeld na een telefonisch verzoek. De rij blijft
     * staan met een datum in unsubscribed_at, dus de verzendlijst slaat hem over
     * en we kunnen later nog terugzien dat en wanneer het gebeurd is.
     */
    public function unsubscribe(Subscriber $subscriber)
    {
        if (! $subscriber->unsubscribed_at) {
            $subscriber->unsubscribed_at = now();
            $subscriber->save();
        }

        return back()->with('status', "{$subscriber->email} staat nu op afgemeld.");
    }

    /**
     * Helemaal weghalen. Bedoeld voor een adres dat bounct: dat bestaat niet
     * meer, dus er valt ook niets te bewaren, en een bouncend adres blijven
     * aanschrijven kost ons de reputatie van het verzenddomein.
     */
    public function destroy(Subscriber $subscriber)
    {
        $email = $subscriber->email;
        $subscriber->delete();

        return back()->with('status', "{$email} is uit de lijst verwijderd.");
    }

    public function export(): StreamedResponse
    {
        $filename = 'desnipperaar-dag-subscribers-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['email', 'lang', 'source', 'gclid', 'utm_source', 'utm_medium', 'utm_campaign', 'landing_page', 'aangemeld', 'afgemeld']);
            Subscriber::orderBy('created_at')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $s) {
                    fputcsv($out, [
                        $s->email, $s->lang, $s->source, $s->gclid,
                        $s->utm_source, $s->utm_medium, $s->utm_campaign, $s->landing_page,
                        optional($s->created_at)->format('Y-m-d H:i'),
                        optional($s->unsubscribed_at)->format('Y-m-d H:i'),
                    ]);
                }
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
