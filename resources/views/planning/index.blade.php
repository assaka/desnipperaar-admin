@extends('layouts.app')
@section('title', 'Planning')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@schedule-x/theme-default@2.15.0/dist/calendar.css">

<div class="flex justify-between items-baseline mb-3">
    <h1 class="text-2xl font-black">Planning</h1>
    <div class="text-xs text-gray-600">Sleep een rit om de datum te wijzigen. Klik een rit om een chauffeur toe te wijzen.</div>
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

<div id="sx-error" style="display:none;padding:12px;background:#FDECEC;border-left:4px solid #D32F2F;color:#8B1A1A;margin-bottom:12px;font-family:monospace;font-size:12px;white-space:pre-wrap;"></div>
<div id="sx-calendar" style="height:78vh;min-height:640px;background:#fff;border:1px solid #DDD;"></div>

{{-- Chauffeur toewijzen vanaf het bord, zonder de rit open te klikken. --}}
<div id="drv-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:50;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:20px;max-width:420px;width:90%;border:2px solid #0A0A0A;">
        <div id="drv-title" class="font-black mb-1"></div>
        <div id="drv-sub" class="text-xs text-gray-600 mb-3"></div>
        <label class="block text-xs font-bold mb-1">Chauffeur</label>
        <select id="drv-select" class="w-full border p-2 mb-3">
            <option value="">— geen chauffeur —</option>
            @foreach ($drivers as $d)
                <option value="{{ $d->id }}">{{ $d->name }}@if ($d->license_last4) (****{{ $d->license_last4 }})@endif</option>
            @endforeach
        </select>
        <div class="flex gap-2 justify-between items-center">
            <a id="drv-open" href="#" class="text-sm underline">Rit openen ›</a>
            <div class="flex gap-2">
                <button id="drv-cancel" type="button" class="px-3 py-1 text-sm border border-gray-500">Sluiten</button>
                <button id="drv-save" type="button" class="px-3 py-1 text-sm font-bold bg-black text-yellow-400">Toewijzen</button>
            </div>
        </div>
    </div>
</div>

{{-- Import map pins one preact + signals@1 across all Schedule-X imports. Prevents esm.sh from pulling @preact/signals@2.x which transitively drags in experimental preact@11 and breaks hooks. --}}
<script type="importmap">
{
    "imports": {
        "preact":               "https://esm.sh/preact@10.19.3",
        "preact/":              "https://esm.sh/preact@10.19.3/",
        "@preact/signals":      "https://esm.sh/@preact/signals@1.3.0?deps=preact@10.19.3",
        "@preact/signals-core": "https://esm.sh/@preact/signals-core@1.6.0"
    }
}
</script>

<script type="module">
const showError = (msg) => {
    const el = document.getElementById('sx-error');
    el.textContent = String(msg);
    el.style.display = 'block';
    console.error('[planning]', msg);
};
window.addEventListener('error',           e => showError('JS error: ' + e.message));
window.addEventListener('unhandledrejection', e => showError('Promise reject: ' + (e.reason?.message || e.reason)));

try {
    // Schedule-X 2.15 is the last release before @preact/signals@2 bump — compatible with preact@10 pinned above.
    // ?external=... tells esm.sh to NOT bundle its own preact/signals — uses the importmap versions instead.
    const ext = '?external=preact,@preact/signals,@preact/signals-core';
    const [
        { createCalendar, viewDay, viewWeek, viewMonthGrid, viewMonthAgenda },
        { createDragAndDropPlugin },
        { createEventsServicePlugin },
        { createCurrentTimePlugin },
    ] = await Promise.all([
        import('https://esm.sh/@schedule-x/calendar@2.15.0'       + ext),
        import('https://esm.sh/@schedule-x/drag-and-drop@2.15.0'  + ext),
        import('https://esm.sh/@schedule-x/events-service@2.15.0' + ext),
        import('https://esm.sh/@schedule-x/current-time@2.15.0'   + ext),
    ]);

    const csrf = '{{ csrf_token() }}';
    const calendars = @json($calendars);

    const today = new Date();
    const fmt = d => d.toISOString().slice(0, 10);
    const start = new Date(today.getFullYear(), today.getMonth() - 2, 1);
    const end   = new Date(today.getFullYear(), today.getMonth() + 6, 0);

    const res = await fetch(`{{ route('planning.events') }}?start=${fmt(start)}&end=${fmt(end)}`, {
        headers: { 'Accept': 'application/json' },
    });
    if (!res.ok) throw new Error('Events feed: HTTP ' + res.status);
    const events = await res.json();

    const windowFromIso = (isoStart) => {
        if (!isoStart || !isoStart.includes(' ')) return 'flexibel';
        const h = parseInt(isoStart.slice(11, 13), 10);
        if (h < 12) return 'ochtend';
        if (h < 17) return 'middag';
        return 'avond';
    };

    const eventsService = createEventsServicePlugin();

    const calendar = createCalendar({
        locale: 'nl-NL',
        firstDayOfWeek: 1,
        views: [viewDay, viewWeek, viewMonthGrid, viewMonthAgenda],
        defaultView: viewWeek.name,
        dayBoundaries: { start: '07:00', end: '21:00' },
        events,
        calendars,
        plugins: [eventsService, createDragAndDropPlugin(15), createCurrentTimePlugin()],
        callbacks: {
            onEventClick(ev) {
                openDriverModal(ev);
            },
            async onEventUpdate(ev) {
                if (ev._type !== 'confirmed') return;

                const newDate   = (ev.start || '').slice(0, 10);
                const newWindow = windowFromIso(ev.start);
                const original  = events.find(e => e.id === ev.id);
                if (!original) return;
                const oldDate   = original.start.slice(0, 10);
                const oldWindow = original._window;

                if (newDate === oldDate && newWindow === oldWindow) return;

                // Een abonnementsrit (bon) krijgt geen bevestigingsmail maar een
                // herinnering de dag ervoor; een losse order wel een bevestiging.
                const note = ev._kind === 'bon'
                    ? 'Klant krijgt de dag ervoor een herinnering met de nieuwe datum.'
                    : 'Klant krijgt een nieuwe bevestigingsmail.';
                if (!confirm(`Verplaatsen naar ${newDate} (${newWindow})?\n${note}`)) {
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
                        body: JSON.stringify({ kind: ev._kind, id: ev._moveId, pickup_date: newDate, window: newWindow }),
                    });
                    if (!r.ok) throw new Error(await r.text());
                    const data = await r.json();
                    if (data.error) alert(data.error);
                    const idx = events.findIndex(e => e.id === ev.id);
                    if (idx >= 0) events[idx] = { ...events[idx], start: ev.start, end: ev.end, _window: newWindow };
                } catch (err) {
                    alert('Fout bij verplaatsen: ' + err.message);
                    eventsService.update(original);
                }
            },
        },
    });

    calendar.render(document.getElementById('sx-calendar'));

    // Chauffeur-toewijzing vanaf het bord. Klikken op een rit opent een keuzelijst
    // in plaats van meteen door te navigeren; verslepen blijft de datum doen.
    const modal = document.getElementById('drv-modal');
    const sel   = document.getElementById('drv-select');
    let current = null;

    window.openDriverModal = (ev) => {
        current = ev;
        document.getElementById('drv-title').textContent = ev.title || ev._customer || '';
        document.getElementById('drv-sub').textContent = ev._address || '';
        document.getElementById('drv-open').href = ev._orderUrl || '#';
        sel.value = ev._driverId ? String(ev._driverId) : '';
        // Een al gereden rit kan niet meer toegewezen worden.
        const done = ev._type === 'done';
        sel.disabled = done;
        document.getElementById('drv-save').disabled = done;
        modal.style.display = 'flex';
    };
    const closeModal = () => { modal.style.display = 'none'; current = null; };
    document.getElementById('drv-cancel').onclick = closeModal;
    modal.onclick = (e) => { if (e.target === modal) closeModal(); };

    document.getElementById('drv-save').onclick = async () => {
        if (!current) return;
        const driverId = sel.value ? parseInt(sel.value, 10) : null;
        try {
            const r = await fetch(`{{ route('planning.assign-driver') }}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ kind: current._kind, id: current._moveId, driver_id: driverId }),
            });
            if (!r.ok) throw new Error(await r.text());
            const data = await r.json();
            // Kleur/lane live bijwerken zodat de toewijzing meteen zichtbaar is.
            const idx = events.findIndex(e => e.id === current.id);
            if (idx >= 0) {
                events[idx] = { ...events[idx], calendarId: data.calendarId, _driverId: data.driverId };
                eventsService.update(events[idx]);
            }
            closeModal();
        } catch (err) {
            alert('Fout bij toewijzen: ' + err.message);
        }
    };
} catch (err) {
    showError('Init: ' + (err?.stack || err?.message || err));
}
</script>

<style>
    /* Brand-match — Schedule-X default theme is already clean; light tweak below. */
    #sx-calendar .sx__event { font-weight:700; font-size:12px; }
    #sx-calendar { --sx-color-primary: #0A0A0A; }
</style>
@endsection
