# Group deals — design

## Goal

Open up ophaalservice (pickup) to cities outside Amsterdam-Noord without taking on
the per-trip cost of solo pickups. A customer in any Dutch city can self-serve a
"group deal": pick a city + pickup date, the deal goes public after admin approval,
and other customers in that city join the same date. On pickup day the route serves
all participants in one trip.

The organizer (deal creator) gets a perk for doing the legwork (currently
"first box free", configurable globally). Travel costs do not exist on quotes today
and are not introduced by this feature, but the schema leaves room to add them later
without rework.

## Out of scope

- Travel cost line items on quotes (deferred — schema-ready).
- Per-deal custom perks (single global perk applies to every deal).
- Per-deal custom thresholds or expiry windows (global settings).
- Brengservice (drop-off) integration. Deals are pickup-only.

## Lifecycle

```
draft → open → closed → completed
            ↘ cancelled
draft ↘ rejected
```

| State       | Meaning                                                      | Set by                          |
|-------------|--------------------------------------------------------------|---------------------------------|
| `draft`     | Customer created the deal, awaiting admin approval.          | Self-serve form                 |
| `open`      | Admin approved. Public on `/groepdeals/{slug}`. Joining on. | Admin                           |
| `closed`    | Joining cutoff hit (T-2 days). Orders materialized.          | Cron / admin trigger            |
| `completed` | Pickup happened.                                             | Pickup confirmation flow        |
| `cancelled` | Organizer or admin cancelled before pickup.                  | Organizer (cancels own join) / Admin |
| `rejected`  | Admin declined the draft (wrong city, unreasonable date…).   | Admin                           |

## Global admin settings

Configured under a single admin screen, applied to every deal:

| Setting                    | Default                | Notes                                              |
|----------------------------|------------------------|----------------------------------------------------|
| Organizer perk type        | `first_box_free`       | Enum, easy to extend later (`percent_off`, etc.)   |
| Organizer perk value       | n/a for `first_box_free` | Numeric, used by parametric perk types            |
| Max joiners per deal       | 30                     | Hard cap — joining closes when reached.            |
| Join cutoff offset (days)  | 2                      | Joining closes T - N days before pickup_date.      |
| Max pickup-date horizon    | 90 days                | Pickup date must be ≤ today + N at creation.       |
| Min pickup-date horizon    | 7 days                 | Pickup date must be ≥ today + N (room to attract). |
| One deal per city per day  | true                   | Hard rule, blocks duplicate-day deals same city.   |

## Schema

### New tables

```sql
CREATE TABLE group_deals (
    id                       BIGSERIAL PRIMARY KEY,
    slug                     VARCHAR(64) UNIQUE NOT NULL,
    city                     VARCHAR(120) NOT NULL,
    pickup_date              DATE NOT NULL,
    organizer_participant_id BIGINT NULL REFERENCES group_deal_participants(id),
    status                   VARCHAR(20) NOT NULL DEFAULT 'draft',
    approved_at              TIMESTAMP NULL,
    closed_at                TIMESTAMP NULL,
    cancelled_at             TIMESTAMP NULL,
    cancellation_reason      TEXT NULL,
    created_at               TIMESTAMP NOT NULL,
    updated_at               TIMESTAMP NOT NULL,
    UNIQUE (city, pickup_date)
);

CREATE TABLE group_deal_participants (
    id              BIGSERIAL PRIMARY KEY,
    group_deal_id   BIGINT NOT NULL REFERENCES group_deals(id) ON DELETE CASCADE,
    customer_name   VARCHAR(180) NOT NULL,
    customer_email  VARCHAR(180) NOT NULL,
    customer_phone  VARCHAR(40)  NULL,
    postcode        VARCHAR(10)  NOT NULL,
    address         VARCHAR(255) NOT NULL,
    box_count       INTEGER      NOT NULL DEFAULT 0,
    container_count INTEGER      NOT NULL DEFAULT 0,
    media_items     JSON         NULL,
    price_snapshot  JSON         NOT NULL,   -- locked quote at join time
    order_id        BIGINT       NULL REFERENCES orders(id),
    created_at      TIMESTAMP    NOT NULL,
    updated_at      TIMESTAMP    NOT NULL
);
```

The cyclic FK between `group_deals.organizer_participant_id` and
`group_deal_participants.group_deal_id` is resolved by inserting the deal first
(without organizer_participant_id), then the organizer's participant row, then
updating the deal with the organizer participant id — all in one transaction.

### Order additions

```sql
ALTER TABLE orders
    ADD COLUMN group_deal_id  BIGINT  NULL REFERENCES group_deals(id),
    ADD COLUMN is_organizer   BOOLEAN NOT NULL DEFAULT false,
    ADD COLUMN quote_locked   BOOLEAN NOT NULL DEFAULT false;
```

`quote_locked = true` tells `Pricing.php` to skip recomputation and read the
already-persisted subtotal/discount/vat/total from the order columns. The
materialization step copies values from `participant.price_snapshot` straight onto
the order before saving.

## Flows

### 1. Customer creates a deal (self-serve)

`POST /api/group-deals` (or a Filament-public form). Validates:

- `city` non-empty
- `pickup_date` in `[today + 7, today + 90]`
- No existing non-cancelled deal for `(city, pickup_date)`
- Embedded organizer order shape (postcode, address, box_count, etc.) — same
  validation as a normal `OrderRequest`

Transaction:

1. `INSERT INTO group_deals (...)` with `status = 'draft'`.
2. `INSERT INTO group_deal_participants (...)` for the organizer, with
   `price_snapshot` = `Pricing::quote(...)` result + organizer perk applied.
3. `UPDATE group_deals SET organizer_participant_id = ?` to point at it.
4. Notify admin (`sales_email` BCC) — new draft awaiting approval.

The organizer sees a "submitted, awaiting approval" page with the eventual
`/groepdeals/{slug}` URL.

### 2. Admin approves / rejects

Filament resource on `group_deals`. Approve transitions `draft → open`, sets
`approved_at`, generates a public slug if not yet set, and emails the organizer.
Reject transitions `draft → rejected` with a `cancellation_reason`, emails the
organizer with the reason.

### 3. Joiner joins

`/groepdeals/{slug}` shows: city, pickup date, joiners-so-far, max cap, organizer
perk note ("organizer gets first box free"), join CTA. Form is the normal order
form, missing the pickup-date field (locked from the deal).

`POST /api/group-deals/{slug}/join`:

- Reject if deal not in `open` state.
- Reject if `participants.count >= max_joiners`.
- Reject if `now > pickup_date - cutoff_offset`.
- Compute `Pricing::quote(...)` using the joiner's postcode (pilot 20% applies if
  in 1020–1039; organizer perk does **not** apply — joiners are not organizers).
- Insert participant row with `price_snapshot`.
- Send confirmation email to joiner; notify admin.

### 4. Cutoff close (cron)

Daily cron at e.g. 02:00:

```
foreach (GroupDeal::open()->where('pickup_date', '<=', today() + cutoff_offset) as $deal):
    DB::transaction(function() use ($deal) {
        foreach ($deal->participants as $p):
            $order = Order::create([
                'group_deal_id'    => $deal->id,
                'is_organizer'     => $p->id === $deal->organizer_participant_id,
                'quote_locked'     => true,
                'customer_name'    => $p->customer_name,
                'customer_email'   => $p->customer_email,
                ... // copy address, box_count, container_count, media_items
                ... // copy subtotal, subtotal_regular, discount, vat, total from price_snapshot
                'pickup_date'      => $deal->pickup_date,
                'pilot'            => substr($p->postcode, 0, 4) is in 1020-1039,
                'first_box_free'   => $p->id === $deal->organizer_participant_id
                                       && perk_type === 'first_box_free',
            ]);
            $p->update(['order_id' => $order->id]);
            Mail::to($p->customer_email)->send(new OrderCreated($order));
        endforeach;
        $deal->update(['status' => 'closed', 'closed_at' => now()]);
    });
endforeach;
```

After this step the deal lives in the regular order pipeline. Pickup-day
completion logic flips status to `completed`.

### 5. Cancellations

| Actor              | When                          | Effect                                                    |
|--------------------|-------------------------------|-----------------------------------------------------------|
| Joiner (non-organizer) | Before close                | Soft-delete participant row. Free their slot.             |
| Joiner (non-organizer) | After close                | Standard order-cancel flow on their `Order`.              |
| Organizer          | Before close, deal still has joiners | Hand off: oldest remaining participant becomes organizer (update `organizer_participant_id`, recompute their `price_snapshot` to apply the perk, refund any difference if perk lowers their price). Old organizer's row soft-deleted. |
| Organizer          | Before close, deal has no other joiners | Deal → `cancelled`. Organizer's participant soft-deleted. |
| Organizer          | After close                  | Standard order-cancel; `is_organizer` flag stays on order; the perk does not transfer post-materialization. |
| Admin              | Any time before close        | Deal → `cancelled`, all participant rows soft-deleted, mass email. |

## Pricing interaction

- `Pricing::quote()` is called once at join time (per participant), result is
  persisted in `price_snapshot`. The materialized order copies those values and is
  flagged `quote_locked` so subsequent reads (invoice, email, admin views) read the
  locked numbers.
- Pilot 20% (1020–1039 postcodes) is computed at join time per participant, baked
  into the snapshot.
- Organizer perk (`first_box_free` today) is applied at the organizer's join only.
  If the organizer is in a pilot postcode, the pilot 20% wins — the perk is
  suppressed (matches the existing rule that pilot replaces other discounts).
- If the organizer is replaced before close (handoff), the new organizer's snapshot
  is recomputed with the perk applied; old organizer's snapshot becomes irrelevant
  (their row is soft-deleted).

## Public surfaces

- `GET /groepdeals` — index of `open` deals, sorted by pickup date. Shows city,
  date, joiners-so-far / cap.
- `GET /groepdeals/{slug}` — single deal with join CTA.
- `POST /api/group-deals` — create draft.
- `POST /api/group-deals/{slug}/join` — join open deal.
- `DELETE /api/group-deals/{slug}/participants/{participant_id}` — cancel own join
  (token-authenticated by email).

## Admin surfaces

- Filament resource `GroupDealResource`: list with status filter, approve / reject
  actions, view participants table inline, manual close action (override cron),
  cancel action with reason.
- Settings screen for the global admin settings table above.

## Email touchpoints

| Event                   | Recipient(s)                        | Template                    |
|-------------------------|-------------------------------------|-----------------------------|
| Draft submitted         | Admin (sales@)                      | new `GroupDealSubmitted`    |
| Draft approved          | Organizer                           | new `GroupDealApproved`     |
| Draft rejected          | Organizer                           | new `GroupDealRejected`     |
| Joiner joined           | Joiner + admin                      | new `GroupDealJoined`       |
| Deal cancelled          | All non-cancelled participants      | new `GroupDealCancelled`    |
| Organizer handoff       | Old organizer + new organizer       | new `GroupDealHandoff`      |
| Deal closed (orders out)| Each participant (existing flow)    | existing `OrderCreated`     |

The existing `OrderCreated` BCC-to-sales@ behaviour (just fixed) carries through
unchanged for materialized orders — group deals slot into the same notification
plumbing.

## Future hooks (do not implement now)

- **Travel cost line:** add `travel_cost` to the Pricing quote return shape and
  to `orders` (column) plus `price_snapshot` (JSON key). When that lands, the deal
  schema gets a `waives_travel_cost BOOLEAN` flag (default true) — currently a no-op.
- **Per-deal threshold:** if the global cap doesn't fit a future use case, add
  `max_joiners` to `group_deals` as a nullable override, falling back to global.
- **Stacking with other promos:** if codes return, `group_deal_participants` could
  carry a `promo_code_used` column without affecting current flows.

## Implementation choices (locked)

- **URL path:** `/groepdeals` (no inter-fix `s`).
- **Slug format:** `{city-slug}-{yyyy-mm-dd}` — predictable, friendlier for
  word-of-mouth sharing than a random token.
- **Cron timezone:** `Europe/Amsterdam` (CET / CEST as the season dictates).
  Pinned in `app/Console/Kernel.php` rather than relying on server `date.timezone`.
- **Soft delete:** `group_deal_participants` uses Laravel `SoftDeletes`. Default
  query scope excludes trashed; admin views use `withTrashed()` for audit.
- **Public listing rendering:** SSR via `desnipperaar.nl/server.js`, same pattern
  as `/blog/` (already intercepts and renders `blog/posts.json` server-side).
  `server.js` adds `/groepdeals` and `/groepdeals/{slug}` routes, fetches from
  `https://admin.desnipperaar.nl/api/group-deals` (and `/api/group-deals/{slug}`)
  on each request, and renders against a static template shell living at
  `groepdeals/index.html` and `groepdeals/_deal.html`. Crawlable, shareable, no
  separate hydration step. Cache the API response for 60s in-process to soften
  burst load if a deal goes viral; bypass cache when the request carries an
  `?nocache=1` query param so admin can preview changes immediately.
