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
