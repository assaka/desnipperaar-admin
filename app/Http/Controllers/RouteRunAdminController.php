<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\RouteRun;
use App\Support\Geocoder;
use Illuminate\Http\Request;

/**
 * Beheer van de open ritten, zie RouteRun. Een rit openzetten is genoeg: vanaf
 * dat moment ziet een klant langs de route op /order gratis ophalen.
 */
class RouteRunAdminController extends Controller
{
    public function index()
    {
        $runs = RouteRun::withCount('orders')
            ->with('anchorOrder:id,order_number')
            ->orderByDesc('is_open')
            ->orderBy('run_date')
            ->get();

        return view('route-runs.index', compact('runs'));
    }

    public function create()
    {
        return view('route-runs.create', ['run' => new RouteRun(['max_detour_km' => 30, 'is_open' => true])]);
    }

    public function store(Request $request)
    {
        $run = RouteRun::create($this->validated($request));

        return redirect()->route('route-runs.edit', $run)->with('status', "Rit {$run->label} staat open.");
    }

    public function edit(RouteRun $routeRun)
    {
        $routeRun->load(['orders' => fn ($q) => $q->orderBy('created_at'), 'anchorOrder']);

        return view('route-runs.edit', ['run' => $routeRun]);
    }

    public function update(Request $request, RouteRun $routeRun)
    {
        $routeRun->update($this->validated($request));

        return redirect()->route('route-runs.edit', $routeRun)->with('status', "Rit {$routeRun->label} bijgewerkt.");
    }

    public function destroy(RouteRun $routeRun)
    {
        $label = $routeRun->label;
        $routeRun->delete();

        return redirect()->route('route-runs.index')->with('status', "Rit {$label} verwijderd.");
    }

    /**
     * Postcode naar coördinaat bij het opslaan, zodat /order niet bij elke
     * bezoeker de bestemming opnieuw hoeft op te zoeken. Een postcode die PDOK
     * niet kent wordt geweigerd: een rit zonder punt kan niemand matchen.
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'label'                => 'required|string|max:120',
            'destination_postcode' => 'required|string|max:10|regex:/^\d{4}\s?[A-Za-z]{2}$/',
            'run_date'             => 'nullable|date',
            'max_detour_km'        => 'required|numeric|min:1|max:200',
            'anchor_order_number'  => 'nullable|string|max:40',
            'notes'                => 'nullable|string|max:2000',
            'is_open'              => 'nullable|boolean',
        ], [], ['destination_postcode' => 'postcode bestemming']);

        $data['destination_postcode'] = strtoupper(preg_replace('/\s+/', '', $data['destination_postcode']));

        $point = Geocoder::forPostcode($data['destination_postcode']);
        if (! $point) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'destination_postcode' => 'Deze postcode is niet te vinden, dus de rit kan niemand matchen.',
            ]);
        }
        $data['lat'] = $point['lat'];
        $data['lon'] = $point['lon'];

        $data['anchor_order_id'] = null;
        if (filled($data['anchor_order_number'] ?? null)) {
            $anchor = Order::where('order_number', trim($data['anchor_order_number']))->first();
            if (! $anchor) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'anchor_order_number' => 'Geen order met dit nummer.',
                ]);
            }
            $data['anchor_order_id'] = $anchor->id;
        }
        unset($data['anchor_order_number']);

        $data['is_open'] = $request->boolean('is_open');

        return $data;
    }
}
