<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\GroupDealCancelled;
use App\Mail\GroupDealJoined;
use App\Mail\GroupDealParticipantJoined;
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

    /** GET /api/group-deals/{slug} — single deal with participant count + organizer first name + privacy-safe public roster. */
    public function show(string $slug): JsonResponse
    {
        $deal = GroupDeal::where('slug', $slug)->firstOrFail();
        if (!in_array($deal->status, [GroupDeal::STATUS_OPEN, GroupDeal::STATUS_CLOSED, GroupDeal::STATUS_COMPLETED], true)) {
            abort(404);
        }
        $deal->loadCount('participants');

        // Privacy-conservative public roster: first name + 4-digit postcode prefix
        // + box/container counts + joined-at. Locked totals, full postcode, full
        // name, email and address are intentionally NOT exposed on the public page.
        $publicRoster = $deal->participants()
            ->orderBy('created_at')
            ->get()
            ->map(function (GroupDealParticipant $row) use ($deal) {
                $first = preg_split('/\s+/', trim($row->customer_name))[0] ?? $row->customer_name;
                $postcodePrefix = substr(preg_replace('/\s+/', '', (string) $row->customer_postcode), 0, 4);
                return [
                    'first_name'      => $first,
                    'postcode_prefix' => $postcodePrefix,
                    'box_count'       => (int) $row->box_count,
                    'container_count' => (int) $row->container_count,
                    'joined_at'       => $row->created_at?->toIso8601String(),
                    'is_organizer'    => $row->id === $deal->organizer_participant_id,
                ];
            })->all();

        return response()->json([
            'deal'   => $this->summarize($deal, detailed: true),
            'roster' => $publicRoster,
        ]);
    }

    /** POST /api/group-deals — customer self-serves a draft deal. */
    public function store(Request $request): JsonResponse
    {
        if (filled($request->input('website'))) {
            return response()->json(['ok' => true], 201);
        }

        $data = $this->validateDealAndOrganizer($request);

        // Cross-field rule: organizer's own contribution can't exceed the group target.
        $orgBoxes      = (int) $data['organizer']['box_count'];
        $orgContainers = (int) ($data['organizer']['container_count'] ?? 0);
        $tgtBoxes      = (int) $data['target_box_count'];
        $tgtContainers = (int) ($data['target_container_count'] ?? 0);
        if ($orgBoxes > $tgtBoxes) {
            return response()->json([
                'ok'    => false,
                'error' => 'Je eigen aantal dozen kan niet groter zijn dan het groepsdoel.',
            ], 422);
        }
        if ($orgContainers > $tgtContainers) {
            return response()->json([
                'ok'    => false,
                'error' => 'Je eigen aantal rolcontainers kan niet groter zijn dan het groepsdoel.',
            ], 422);
        }

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
            Mail::to($participant->customer_email)->send(new GroupDealJoined($deal, $participant));
        } catch (\Throwable $e) {
            report($e);
        }

        // Notify the organizer with updated stats. Skip if the joiner somehow IS
        // the organizer (defensive — the join flow shouldn't allow this), or if
        // they share the same email (avoid sending two near-identical mails).
        $organizer = $deal->organizerParticipant;
        if ($organizer
            && $organizer->id !== $participant->id
            && strcasecmp($organizer->customer_email, $participant->customer_email) !== 0) {
            try {
                Mail::send(new GroupDealParticipantJoined($deal, $participant, $organizer));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response()->json([
            'ok'          => true,
            'participant' => [
                'id'           => $participant->id,
                'manage_token' => $participant->manage_token,
                'manage_url'   => $participant->manageUrl(),
                'price'        => [
                    'subtotal' => $snapshot['subtotal'],
                    'vat'      => $snapshot['vat'],
                    'total'    => $snapshot['total'],
                ],
            ],
            'deal' => $this->summarize($deal->fresh()->loadCount('participants'), detailed: true),
        ], 201);
    }

    /** GET /api/group-deals/manage/{token} — token-gated participant view. */
    public function manageShow(string $token): JsonResponse
    {
        $p = GroupDealParticipant::where('manage_token', $token)->firstOrFail();
        $deal = $p->groupDeal()->withCount('participants')->firstOrFail();

        $isOrganizer = $p->id === $deal->organizer_participant_id;

        $participant = [
            'id'                => $p->id,
            'manage_token'      => $p->manage_token,
            'is_organizer'      => $isOrganizer,
            'customer_name'     => $p->customer_name,
            'customer_email'    => $p->customer_email,
            'customer_phone'    => $p->customer_phone,
            'customer_postcode' => $p->customer_postcode,
            'customer_address'  => $p->customer_address,
            'box_count'         => $p->box_count,
            'container_count'   => $p->container_count,
            'notes'             => $p->notes,
            'price_snapshot'    => $p->price_snapshot,
            'order_id'          => $p->order_id,
        ];

        $payload = [
            'deal'        => $this->summarize($deal, detailed: true),
            'participant' => $participant,
        ];

        // Organizer-only roster: hide soft-deleted, default privacy-safe fields.
        if ($isOrganizer) {
            $payload['roster'] = $deal->participants()
                ->orderBy('created_at')
                ->get()
                ->map(function (GroupDealParticipant $row) use ($deal) {
                    $first = preg_split('/\s+/', trim($row->customer_name))[0] ?? $row->customer_name;
                    return [
                        'id'              => $row->id,
                        'first_name'      => $first,
                        'postcode'        => $row->customer_postcode,
                        'box_count'       => $row->box_count,
                        'container_count' => $row->container_count,
                        'total'           => $row->price_snapshot['total'] ?? null,
                        'joined_at'       => $row->created_at?->toIso8601String(),
                        'is_organizer'    => $row->id === $deal->organizer_participant_id,
                    ];
                })->all();
        }

        return response()->json($payload);
    }

    /** PATCH /api/group-deals/manage/{token} — update own participant fields, recompute snapshot. */
    public function manageUpdate(Request $request, string $token): JsonResponse
    {
        $p = GroupDealParticipant::where('manage_token', $token)->firstOrFail();
        $deal = $p->groupDeal;

        // Block edits once the deal has closed (orders materialized) or the join cutoff has passed.
        if (!in_array($deal->status, [GroupDeal::STATUS_DRAFT, GroupDeal::STATUS_OPEN], true)) {
            return response()->json(['ok' => false, 'error' => 'Deze groepsdeal accepteert geen wijzigingen meer.'], 422);
        }
        if (now() >= $deal->joinCutoffAt()) {
            return response()->json(['ok' => false, 'error' => 'De wijzigtermijn is verstreken.'], 422);
        }

        $data = $request->validate([
            'customer_name'                 => ['required', 'string', 'max:180'],
            'customer_email'                => ['required', 'email', 'max:180'],
            'customer_phone'                => ['nullable', 'string', 'max:40'],
            'customer_postcode'             => ['required', 'string', 'max:10'],
            'customer_address'              => ['required', 'string', 'max:255'],
            'box_count'                     => ['required', 'integer', 'min:0', 'max:200'],
            'container_count'               => ['nullable', 'integer', 'min:0', 'max:50'],
            'notes'                         => ['nullable', 'string', 'max:2000'],
            // Deal-level fields — silently ignored unless the participant is the organizer.
            'deal'                          => ['nullable', 'array'],
            'deal.target_box_count'         => ['nullable', 'integer', 'min:1', 'max:10000'],
            'deal.target_container_count'   => ['nullable', 'integer', 'min:0', 'max:1000'],
            'deal.pickup_date'              => ['nullable', 'date'],
        ]);

        $isOrganizer = $p->id === $deal->organizer_participant_id;

        // Apply deal-level edits before participant cap-check, so a target raise
        // can unblock a simultaneous organizer-bijdrage raise in one PATCH.
        if ($isOrganizer && !empty($data['deal'])) {
            $err = $this->applyOrganizerDealEdits($deal, $data['deal']);
            if ($err) return $err;
            $deal->refresh();
        }

        // Organizer's own contribution is capped by the group target.
        if ($isOrganizer) {
            if ((int) $data['box_count'] > (int) $deal->target_box_count) {
                return response()->json([
                    'ok'    => false,
                    'error' => 'Je eigen aantal dozen kan niet groter zijn dan het groepsdoel.',
                ], 422);
            }
            if ((int) ($data['container_count'] ?? 0) > (int) $deal->target_container_count) {
                return response()->json([
                    'ok'    => false,
                    'error' => 'Je eigen aantal rolcontainers kan niet groter zijn dan het groepsdoel.',
                ], 422);
            }
        }

        $isPilot   = Pricing::isPilotPostcode($data['customer_postcode']);
        $perkType  = config('desnipperaar.group_deal.organizer_perk_type');
        $applyPerk = $isOrganizer && $perkType === 'first_box_free' && !$isPilot;

        $snapshot = Pricing::snapshot(
            (int) $data['box_count'],
            (int) ($data['container_count'] ?? 0),
            $p->media_items,
            $isPilot,
            $applyPerk,
        );

        $p->update([
            'customer_name'     => $data['customer_name'],
            'customer_email'    => strtolower(trim($data['customer_email'])),
            'customer_phone'    => $data['customer_phone'] ?? null,
            'customer_postcode' => $data['customer_postcode'],
            'customer_address'  => $data['customer_address'],
            'box_count'         => (int) $data['box_count'],
            'container_count'   => (int) ($data['container_count'] ?? 0),
            'notes'             => $data['notes'] ?? null,
            'price_snapshot'    => $snapshot,
        ]);

        return response()->json([
            'ok'          => true,
            'participant' => [
                'id'    => $p->id,
                'price' => [
                    'subtotal' => $snapshot['subtotal'],
                    'vat'      => $snapshot['vat'],
                    'total'    => $snapshot['total'],
                ],
            ],
        ]);
    }

    /** DELETE /api/group-deals/manage/{token} — cancel own join (token-gated). */
    public function manageDelete(string $token): JsonResponse
    {
        $p = GroupDealParticipant::where('manage_token', $token)->firstOrFail();
        $deal = $p->groupDeal;

        $this->cancelParticipant($deal, $p);

        return response()->json(['ok' => true]);
    }

    /** Apply organizer-driven edits to a GroupDeal (target volumes, pickup_date in draft).
     *  Returns a JsonResponse on validation failure, or null on success — caller must
     *  short-circuit when non-null. */
    private function applyOrganizerDealEdits(GroupDeal $deal, array $dealData): ?JsonResponse
    {
        $update = [];

        // target_box_count: editable in draft + open. Floored at current filled total
        // so a downgrade can't orphan participants who already joined.
        if (array_key_exists('target_box_count', $dealData) && $dealData['target_box_count'] !== null) {
            $newTarget = (int) $dealData['target_box_count'];
            $filled = (int) $deal->participants()->sum('box_count');
            if ($newTarget < $filled) {
                return response()->json([
                    'ok'    => false,
                    'error' => "Doel dozen ({$newTarget}) kan niet lager zijn dan al ingeschreven aantal ({$filled}).",
                ], 422);
            }
            $update['target_box_count'] = $newTarget;
        }

        if (array_key_exists('target_container_count', $dealData) && $dealData['target_container_count'] !== null) {
            $newTarget = (int) $dealData['target_container_count'];
            $filled = (int) $deal->participants()->sum('container_count');
            if ($newTarget < $filled) {
                return response()->json([
                    'ok'    => false,
                    'error' => "Doel rolcontainers ({$newTarget}) kan niet lager zijn dan al ingeschreven aantal ({$filled}).",
                ], 422);
            }
            $update['target_container_count'] = $newTarget;
        }

        // pickup_date: only editable in draft. After approval the day is frozen since
        // joiners signed up for that specific date. No-op when the submitted value
        // equals the current pickup_date — this lets the manage form post the field
        // unconditionally without erroring on open deals.
        if (array_key_exists('pickup_date', $dealData) && $dealData['pickup_date'] !== null) {
            $newDate    = \Illuminate\Support\Carbon::parse($dealData['pickup_date']);
            $newDateStr = $newDate->toDateString();
            $changed    = $newDateStr !== $deal->pickup_date->toDateString();
            if ($changed) {
                if ($deal->status !== GroupDeal::STATUS_DRAFT) {
                    return response()->json([
                        'ok'    => false,
                        'error' => 'Ophaaldag kan alleen in concept-fase gewijzigd worden.',
                    ], 422);
                }
                $minHorizon = (int) config('desnipperaar.group_deal.min_horizon_days', 7);
                $maxHorizon = (int) config('desnipperaar.group_deal.max_horizon_days', 90);
                $minAllowed = now()->addDays($minHorizon)->startOfDay();
                $maxAllowed = now()->addDays($maxHorizon)->endOfDay();
                if ($newDate->lt($minAllowed) || $newDate->gt($maxAllowed)) {
                    return response()->json([
                        'ok'    => false,
                        'error' => "Ophaaldag moet tussen {$minAllowed->toDateString()} en {$maxAllowed->toDateString()} liggen.",
                    ], 422);
                }
                $clash = GroupDeal::where('city', $deal->city)
                    ->whereDate('pickup_date', $newDateStr)
                    ->where('id', '!=', $deal->id)
                    ->whereNotIn('status', [GroupDeal::STATUS_REJECTED, GroupDeal::STATUS_CANCELLED])
                    ->exists();
                if ($clash) {
                    return response()->json([
                        'ok'    => false,
                        'error' => 'Er bestaat al een groepsdeal voor deze stad op deze datum.',
                    ], 422);
                }
                $update['pickup_date'] = $newDateStr;
                // Slug encodes the date; regenerate so the public URL stays consistent.
                $update['slug'] = GroupDeal::generateSlug($deal->city, $newDate);
            }
        }

        if (!empty($update)) {
            $deal->update($update);
        }
        return null;
    }

    /** Soft-delete a participant; if they were the organizer, hand off to the next-oldest
     *  remaining participant (or cancel the deal if none). Recomputes the new organizer's
     *  snapshot so the perk is applied. Used by the manage-token DELETE endpoint. */
    private function cancelParticipant(GroupDeal $deal, GroupDealParticipant $p): void
    {
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
