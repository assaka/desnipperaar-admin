<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\GroupDealCancelled;
use App\Mail\GroupDealJoined;
use App\Mail\GroupDealReceived;
use App\Mail\GroupDealSubmitted;
use App\Models\GroupDeal;
use App\Models\GroupDealParticipant;
use App\Support\Pricing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class GroupDealController extends Controller
{
    /** GET /api/group-deals — public listing of joinable + recently-closed deals. */
    public function index(Request $request): JsonResponse
    {
        $deals = GroupDeal::query()
            ->whereIn('status', [GroupDeal::STATUS_OPEN])
            ->orderBy('pickup_date')
            ->withCount('participants')
            ->get()
            ->map(fn ($d) => $this->summarize($d));

        return response()->json(['deals' => $deals]);
    }

    /** GET /api/group-deals/{slug} — single deal with participant count + organizer first name. */
    public function show(string $slug): JsonResponse
    {
        $deal = GroupDeal::where('slug', $slug)->firstOrFail();
        if (!in_array($deal->status, [GroupDeal::STATUS_OPEN, GroupDeal::STATUS_CLOSED, GroupDeal::STATUS_COMPLETED], true)) {
            abort(404);
        }
        $deal->loadCount('participants');
        return response()->json(['deal' => $this->summarize($deal, detailed: true)]);
    }

    /** POST /api/group-deals — customer self-serves a draft deal. */
    public function store(Request $request): JsonResponse
    {
        if (filled($request->input('website'))) {
            return response()->json(['ok' => true], 201);
        }

        $data = $this->validateDealAndOrganizer($request);

        // One-deal-per-city-per-day rule.
        if (config('desnipperaar.group_deal.one_per_city_per_day')) {
            $clash = GroupDeal::where('city', $data['city'])
                ->where('pickup_date', $data['pickup_date'])
                ->whereNotIn('status', [GroupDeal::STATUS_REJECTED, GroupDeal::STATUS_CANCELLED])
                ->exists();
            if ($clash) {
                return response()->json([
                    'ok' => false,
                    'error' => 'Er bestaat al een groepsdeal voor deze stad op deze datum.',
                ], 422);
            }
        }

        $perkType    = config('desnipperaar.group_deal.organizer_perk_type');
        $isPilot     = Pricing::isPilotPostcode($data['organizer']['customer_postcode']);
        $applyPerk   = $perkType === 'first_box_free' && !$isPilot;

        $snapshot = Pricing::snapshot(
            (int) $data['organizer']['box_count'],
            (int) $data['organizer']['container_count'],
            $data['organizer']['media_items'] ?? null,
            $isPilot,
            $applyPerk,
        );

        $deal = DB::transaction(function () use ($data, $snapshot) {
            $deal = GroupDeal::create([
                'slug'                   => GroupDeal::generateSlug($data['city'], \Illuminate\Support\Carbon::parse($data['pickup_date'])),
                'city'                   => $data['city'],
                'pickup_date'            => $data['pickup_date'],
                'target_box_count'       => (int) $data['target_box_count'],
                'target_container_count' => (int) ($data['target_container_count'] ?? 0),
                'status'                 => GroupDeal::STATUS_DRAFT,
            ]);

            $organizer = GroupDealParticipant::create([
                'group_deal_id'     => $deal->id,
                'customer_name'     => $data['organizer']['customer_name'],
                'customer_email'    => strtolower(trim($data['organizer']['customer_email'])),
                'customer_phone'    => $data['organizer']['customer_phone'] ?? null,
                'customer_postcode' => $data['organizer']['customer_postcode'],
                'customer_address'  => $data['organizer']['customer_address'],
                'customer_city'     => $data['city'],
                'box_count'         => (int) $data['organizer']['box_count'],
                'container_count'   => (int) ($data['organizer']['container_count'] ?? 0),
                'media_items'       => $data['organizer']['media_items'] ?? null,
                'notes'             => $data['organizer']['notes'] ?? null,
                'price_snapshot'    => $snapshot,
            ]);

            $deal->update(['organizer_participant_id' => $organizer->id]);
            return $deal->fresh(['organizerParticipant']);
        });

        try {
            Mail::send(new GroupDealSubmitted($deal));
        } catch (\Throwable $e) {
            report($e);
        }

        if ($deal->organizerParticipant) {
            try {
                Mail::to($deal->organizerParticipant->customer_email)
                    ->send(new GroupDealReceived($deal));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response()->json([
            'ok'   => true,
            'deal' => $this->summarize($deal, detailed: true),
        ], 201);
    }

    /** POST /api/group-deals/{slug}/join — joiner self-joins an open deal. */
    public function join(Request $request, string $slug): JsonResponse
    {
        if (filled($request->input('website'))) {
            return response()->json(['ok' => true], 201);
        }

        $deal = GroupDeal::where('slug', $slug)->firstOrFail();

        if (!$deal->joiningOpen()) {
            return response()->json([
                'ok' => false,
                'error' => 'Deze groepsdeal accepteert geen nieuwe deelnemers meer.',
            ], 422);
        }

        $data = $request->validate([
            'customer_name'     => ['required', 'string', 'max:180'],
            'customer_email'    => ['required', 'email', 'max:180'],
            'customer_phone'    => ['nullable', 'string', 'max:40'],
            'customer_postcode' => ['required', 'string', 'max:10'],
            'customer_address'  => ['required', 'string', 'max:255'],
            'box_count'         => ['required', 'integer', 'min:0', 'max:200'],
            'container_count'   => ['nullable', 'integer', 'min:0', 'max:50'],
            'media_items'       => ['nullable', 'array'],
            'notes'             => ['nullable', 'string', 'max:2000'],
            'website'           => ['nullable'],
        ]);

        // Reject duplicate joins by the same email on the same deal.
        $exists = $deal->participants()->where('customer_email', strtolower(trim($data['customer_email'])))->exists();
        if ($exists) {
            return response()->json([
                'ok' => false,
                'error' => 'Je hebt je al ingeschreven voor deze groepsdeal.',
            ], 422);
        }

        $isPilot = Pricing::isPilotPostcode($data['customer_postcode']);
        // Joiners never get the organizer perk.
        $snapshot = Pricing::snapshot(
            (int) $data['box_count'],
            (int) ($data['container_count'] ?? 0),
            $data['media_items'] ?? null,
            $isPilot,
            firstBoxFree: false,
        );

        $participant = GroupDealParticipant::create([
            'group_deal_id'     => $deal->id,
            'customer_name'     => $data['customer_name'],
            'customer_email'    => strtolower(trim($data['customer_email'])),
            'customer_phone'    => $data['customer_phone'] ?? null,
            'customer_postcode' => $data['customer_postcode'],
            'customer_address'  => $data['customer_address'],
            'customer_city'     => $deal->city,
            'box_count'         => (int) $data['box_count'],
            'container_count'   => (int) ($data['container_count'] ?? 0),
            'media_items'       => $data['media_items'] ?? null,
            'notes'             => $data['notes'] ?? null,
            'price_snapshot'    => $snapshot,
        ]);

        try {
            Mail::send(new GroupDealJoined($deal, $participant));
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json([
            'ok'          => true,
            'participant' => [
                'id'    => $participant->id,
                'price' => [
                    'subtotal' => $snapshot['subtotal'],
                    'vat'      => $snapshot['vat'],
                    'total'    => $snapshot['total'],
                ],
            ],
            'deal' => $this->summarize($deal->fresh()->loadCount('participants'), detailed: true),
        ], 201);
    }

    /** DELETE /api/group-deals/{slug}/participants/{participant} — cancel own join (email-gated). */
    public function cancel(Request $request, string $slug, int $participant): JsonResponse
    {
        $deal = GroupDeal::where('slug', $slug)->firstOrFail();
        $p = $deal->participants()->where('id', $participant)->firstOrFail();

        $email = strtolower(trim($request->query('email', $request->input('email', ''))));
        if ($email === '' || $email !== $p->customer_email) {
            return response()->json(['ok' => false, 'error' => 'Onbekend e-mailadres.'], 403);
        }

        // If the canceller was the organizer, hand off to the oldest remaining
        // non-cancelled participant; if none, cancel the deal.
        DB::transaction(function () use ($deal, $p) {
            $isOrganizer = $p->id === $deal->organizer_participant_id;
            $p->delete();

            if (!$isOrganizer) {
                return;
            }

            $next = $deal->participants()
                ->where('id', '!=', $p->id)
                ->orderBy('created_at')
                ->first();

            if (!$next) {
                $deal->update([
                    'status'              => GroupDeal::STATUS_CANCELLED,
                    'cancelled_at'        => now(),
                    'cancellation_reason' => 'Organisator heeft geannuleerd; geen overige deelnemers.',
                ]);
                try {
                    Mail::send(new GroupDealCancelled($deal));
                } catch (\Throwable $e) {
                    report($e);
                }
                return;
            }

            // Hand off: recompute the new organizer's snapshot with the perk applied
            // (subject to the pilot-replaces-perk rule).
            $perkType  = config('desnipperaar.group_deal.organizer_perk_type');
            $isPilot   = Pricing::isPilotPostcode($next->customer_postcode);
            $applyPerk = $perkType === 'first_box_free' && !$isPilot;

            $snapshot = Pricing::snapshot(
                (int) $next->box_count,
                (int) $next->container_count,
                $next->media_items,
                $isPilot,
                $applyPerk,
            );
            $next->update(['price_snapshot' => $snapshot]);
            $deal->update(['organizer_participant_id' => $next->id]);
        });

        return response()->json(['ok' => true]);
    }

    private function validateDealAndOrganizer(Request $request): array
    {
        $minHorizon = (int) config('desnipperaar.group_deal.min_horizon_days', 7);
        $maxHorizon = (int) config('desnipperaar.group_deal.max_horizon_days', 90);

        return $request->validate([
            'city'                          => ['required', 'string', 'max:120'],
            'pickup_date'                   => [
                'required', 'date',
                'after_or_equal:' . now()->addDays($minHorizon)->toDateString(),
                'before_or_equal:' . now()->addDays($maxHorizon)->toDateString(),
            ],
            'target_box_count'              => ['required', 'integer', 'min:1', 'max:10000'],
            'target_container_count'        => ['nullable', 'integer', 'min:0', 'max:1000'],
            'organizer'                     => ['required', 'array'],
            'organizer.customer_name'       => ['required', 'string', 'max:180'],
            'organizer.customer_email'      => ['required', 'email', 'max:180'],
            'organizer.customer_phone'      => ['nullable', 'string', 'max:40'],
            'organizer.customer_postcode'   => ['required', 'string', 'max:10'],
            'organizer.customer_address'    => ['required', 'string', 'max:255'],
            'organizer.box_count'           => ['required', 'integer', 'min:0', 'max:200'],
            'organizer.container_count'     => ['nullable', 'integer', 'min:0', 'max:50'],
            'organizer.media_items'         => ['nullable', 'array'],
            'organizer.notes'               => ['nullable', 'string', 'max:2000'],
            'website'                       => ['nullable'],
        ]);
    }

    private function summarize(GroupDeal $deal, bool $detailed = false): array
    {
        $cap        = (int) config('desnipperaar.group_deal.max_joiners', 30);
        $joinedCount = $deal->participants_count ?? $deal->participants()->count();

        $organizerFirstName = null;
        if ($detailed && $deal->organizerParticipant) {
            $parts = preg_split('/\s+/', trim($deal->organizerParticipant->customer_name));
            $organizerFirstName = $parts[0] ?? null;
        }

        $progress = $deal->participants()
            ->selectRaw('COALESCE(SUM(box_count), 0) AS boxes, COALESCE(SUM(container_count), 0) AS containers')
            ->first();

        return array_filter([
            'slug'                   => $deal->slug,
            'city'                   => $deal->city,
            'pickup_date'            => $deal->pickup_date->toDateString(),
            'status'                 => $deal->status,
            'joined'                 => $joinedCount,
            'cap'                    => $cap,
            'join_cutoff_at'         => $deal->joinCutoffAt()->toIso8601String(),
            'joining_open'           => $deal->joiningOpen(),
            'organizer_first_name'   => $organizerFirstName,
            'target_box_count'       => (int) $deal->target_box_count,
            'target_container_count' => (int) $deal->target_container_count,
            'filled_box_count'       => (int) ($progress->boxes ?? 0),
            'filled_container_count' => (int) ($progress->containers ?? 0),
        ], fn ($v) => $v !== null);
    }
}
