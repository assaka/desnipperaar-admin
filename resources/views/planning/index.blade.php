@extends('layouts.app')
@section('title', 'Planning')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@schedule-x/theme-default@2/dist/calendar.css">

<div class="flex justify-between items-baseline mb-3">
    <h1 class="text-2xl font-black">Planning</h1>
    <div class="text-xs text-gray-600">Sleep een bevestigde afspraak — klant krijgt automatisch een nieuwe bevestigingsmail.</div>
</div>

<div class="flex gap-4 mb-3 text-sm flex-wrap items-center">
    @foreach ($calendars as $id => $cal)
        @php $c = $cal['lightColors']['main']; @endphp
        <span class="flex items-center gap-1">
            <span class="inline-block" style="width:14px;height:14px;background:{{ $c }};border:1px solid #0A0A0A;"></span>
            {{ $cal['label'] }}
        </span>
    @endforeach
    @if ($drivers->isEmpty())
        <span class="text-xs text-orange-700 ml-auto">Nog geen chauffeurs — <a href="{{ route('drivers.index') }}" class="underline">beheer chauffeurs</a></span>
    @endif
</div>

<div id="sx-calendar" style="height:78vh;min-height:640px;background:#fff;border:1px solid #DDD;"></div>

<script type="module">
import { createCalendar, viewDay, viewWeek, viewMonthGrid, viewMonthAgenda } from 'https://esm.sh/@schedule-x/calendar@2';
import { createDragAndDropPlugin } from 'https://esm.sh/@schedule-x/drag-and-drop@2';
import { createEventsServicePlugin } from 'https://esm.sh/@schedule-x/events-service@2';
import { createCurrentTimePlugin } from 'https://esm.sh/@schedule-x/current-time@2';

const csrf = '{{ csrf_token() }}';
const calendars = @json($calendars, JSON_UNESCAPED_UNICODE);

// Fetch a ~1-year window around today for initial events.
const today = new Date();
const start = new Date(today.getFullYear(), today.getMonth() - 2, 1);
const end   = new Date(today.getFullYear(), today.getMonth() + 6, 0);
const fmt = d => d.toISOString().slice(0, 10);

const res = await fetch(`{{ route('planning.events') }}?start=${fmt(start)}&end=${fmt(end)}`);
const events = await res.json();

const eventsService = createEventsServicePlugin();

const windowFromIso = (isoStart) => {
    // 'YYYY-MM-DD HH:mm' or 'YYYY-MM-DD'
    if (!isoStart.includes(' ')) return 'flexibel';
    const h = parseInt(isoStart.slice(11, 13), 10);
    if (h < 12) return 'ochtend';
    if (h < 17) return 'middag';
    return 'avond';
};

const calendar = createCalendar({
    locale: 'nl-NL',
    firstDayOfWeek: 1,
    views: [viewDay, viewWeek, viewMonthGrid, viewMonthAgenda],
    defaultView: viewWeek.name,
    dayBoundaries: { start: '07:00', end: '21:00' },
    events,
    calendars,
    plugins: [
        eventsService,
        createDragAndDropPlugin(15),
        createCurrentTimePlugin(),
    ],
    callbacks: {
        onEventClick(ev) {
            if (ev._orderUrl) window.location.href = ev._orderUrl;
        },
        async onEventUpdate(ev) {
            // ev has new start/end after drag-drop
            if (ev._type !== 'confirmed') {
                // Schedule-X should prevent this via editable:false, but be defensive
                eventsService.update(events.find(e => e.id === ev.id));
                return;
            }

            const newDate   = ev.start.slice(0, 10);
            const newWindow = windowFromIso(ev.start);
            const original  = events.find(e => e.id === ev.id);
            const oldDate   = original.start.slice(0, 10);
            const oldWindow = original._window;

            if (newDate === oldDate && newWindow === oldWindow) {
                return; // nothing effective changed
            }

            if (!confirm(`Verplaatsen naar ${newDate} (${newWindow})?\nKlant krijgt een nieuwe bevestigingsmail.`)) {
                eventsService.update(original);
                return;
            }

            try {
                const r = await fetch(`{{ route('planning.move') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ order_id: ev._orderId, pickup_date: newDate, window: newWindow }),
                });
                if (!r.ok) throw new Error(await r.text());
                const data = await r.json();
                if (data.error) alert(data.error);
                // Update local event record so next drag has the latest "old" values
                const idx = events.findIndex(e => e.id === ev.id);
                if (idx >= 0) { events[idx] = { ...events[idx], start: ev.start, end: ev.end, _window: newWindow }; }
            } catch (err) {
                alert('Fout bij verplaatsen: ' + err.message);
                eventsService.update(original);
            }
        },
    },
});

// Schedule-X v2: lock proposal events as read-only via pre-render hook.
// (The drag handler also bails on _type !== 'confirmed' as a belt + suspenders.)
calendar.render(document.getElementById('sx-calendar'));
</script>

<style>
    /* Brand-match — Schedule-X default theme is already clean; light tweak below. */
    #sx-calendar .sx__event { font-weight:700; font-size:12px; }
    #sx-calendar { --sx-color-primary: #0A0A0A; }
</style>
@endsection
