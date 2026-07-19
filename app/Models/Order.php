<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    const TYPE_DIRECT        = 'direct';
    const TYPE_QUOTE         = 'quote';
    const TYPE_ABONNEMENT    = 'abonnement';

    /**
     * Looptijd en frequentie van een abonnement. De labels staan hier en niet in
     * de views, omdat lijst, detailpagina en mail dezelfde tekst moeten tonen.
     * De sleutels komen één op één van het aanvraagformulier op desnipperaar.nl.
     */
    /** Looptijden die op het aanvraagformulier gekozen kunnen worden. */
    const SUB_TERMS = [
        'flex' => 'Flex (min. 3 maanden, daarna maandelijks)',
        'vast' => 'Vast (12 maanden)',
        'jaar' => 'Jaarbetaling (12 maanden vooruit)',
    ];

    /**
     * Losse maandtermijn zonder minimum. Hier komt een Vast of Jaar terecht na
     * afloop van de eerste twaalf maanden, want de site belooft dat het daarna
     * maandelijks doorloopt en op elk moment stopgezet kan worden. Bewust geen
     * onderdeel van SUB_TERMS: niemand kiest dit bij de aanvraag, je groeit
     * erin.
     */
    const SUB_TERM_MONTHLY = 'maandelijks';

    const SUB_TERM_LABELS = self::SUB_TERMS + [
        self::SUB_TERM_MONTHLY => 'Maandelijks (na de eerste termijn, altijd opzegbaar)',
    ];

    const SUB_FREQS = [
        '4w'  => '1x per 4 weken',
        '2w'  => '1x per 2 weken',
        '1w'  => '1x per week',
        '2pw' => '2x per week',
    ];

    const STATE_NIEUW        = 'nieuw';
    const STATE_BEVESTIGD    = 'bevestigd';
    const STATE_OPGEHAALD    = 'opgehaald';
    const STATE_VERNIETIGD   = 'vernietigd';
    const STATE_AFGESLOTEN   = 'afgesloten';

    protected $fillable = [
        'order_number',
        'quote_reference',
        'reply_ref',
        'type',
        'customer_id',
        'created_by_user_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_address',
        'customer_postcode',
        'customer_city',
        'locale',
        'customer_reference',
        'delivery_mode',
        'box_count',
        'container_count',
        'media_items',
        'notes',
        'state',
        'pilot',
        'pickup_date',
        'pickup_window',
        'pickup_note',
        'duration_minutes',
        'first_box_free',
        'pickup_cost',
        'pickup_km',
        'pickup_choice',
        'quoted_amount_excl_btw',
        'quote_body',
        'quote_lines',
        'quote_sent_at',
        'quote_valid_until',
        'quote_accepted_at',
        'quote_acceptance_ip',
        'quote_token',
        'sub_term',
        'sub_freq',
        'sub_pickup_weekday',
        'sub_price_excl_btw',
        'sub_active_from',
        'sub_term_started_on',
        'sub_renewal_notified_at',
        'sub_terminated_at',
        'sub_ends_on',
        'sub_last_invoiced_period',
        'sub_billing_provider',
        'sub_billing_ref',
        'public_token',
        'reschedule_requested_at',
        'reschedule_requested_date',
        'reschedule_requested_window',
        'reschedule_notes',
        'group_deal_id',
        'subscription_order_id',
        'subscription_scheduled_for',
        'is_organizer',
        'quote_locked',
        'price_snapshot',
    ];

    protected $casts = [
        'media_items' => 'array',
        'quote_lines' => 'array',
        'pilot' => 'boolean',
        'first_box_free' => 'boolean',
        'pickup_date' => 'date',
        'quote_sent_at' => 'datetime',
        'quote_valid_until' => 'date',
        'quote_accepted_at' => 'datetime',
        'quoted_amount_excl_btw' => 'decimal:2',
        'sub_price_excl_btw' => 'decimal:2',
        'sub_active_from' => 'date',
        'sub_term_started_on' => 'date',
        'sub_renewal_notified_at' => 'datetime',
        'sub_terminated_at' => 'datetime',
        'sub_ends_on' => 'date',
        'sub_last_invoiced_period' => 'date',
        'subscription_scheduled_for' => 'date',
        'sub_pickup_weekday' => 'integer',
        'pickup_cost' => 'decimal:2',
        'pickup_km' => 'integer',
        'box_count' => 'integer',
        'container_count' => 'integer',
        'duration_minutes' => 'integer',
        'reschedule_requested_at'   => 'datetime',
        'reschedule_requested_date' => 'date',
        'is_organizer' => 'boolean',
        'quote_locked' => 'boolean',
        'price_snapshot' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->reply_ref)) {
                $order->reply_ref = self::generateReplyRef();
            }
        });
    }

    /** Opaque, collision-checked reply reference (lowercase [a-z0-9], 10 chars). */
    public static function generateReplyRef(): string
    {
        do {
            $ref = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(10));
        } while (self::where('reply_ref', $ref)->exists());

        return $ref;
    }

    /** Guarantee a reply reference exists (backfills legacy rows lazily) and return it. */
    public function ensureReplyRef(): string
    {
        if (empty($this->reply_ref)) {
            $this->reply_ref = self::generateReplyRef();
            $this->saveQuietly();
        }

        return $this->reply_ref;
    }

    /** The subject tag replies must keep for the inbound matcher, e.g. "[ref:a1b2c3d4e5]". */
    public function replyTag(): string
    {
        return '[ref:'.$this->ensureReplyRef().']';
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** Resolve the user whose name/email should appear as From: on mails about this order. */
    public function senderUser(): ?User
    {
        return $this->createdBy ?? User::orderBy('id')->first();
    }

    public function bons()
    {
        return $this->hasMany(Bon::class);
    }

    public function certificate()
    {
        return $this->hasOne(Certificate::class);
    }

    public function messages()
    {
        return $this->hasMany(OrderMessage::class)->orderByRaw('occurred_at DESC NULLS LAST')->orderByDesc('id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function groupDeal()
    {
        return $this->belongsTo(GroupDeal::class);
    }

    public function groupDealParticipant()
    {
        return $this->hasOne(GroupDealParticipant::class);
    }

    public function isQuoteExpired(): bool
    {
        return $this->quote_valid_until && $this->quote_valid_until->isPast();
    }

    public static function generateOrderNumber(): string
    {
        $prefix = config('desnipperaar.order.prefix');
        $year   = now()->year;
        $start  = config('desnipperaar.order.start');

        $last = self::where('order_number', 'like', "{$prefix}-{$year}-%")
            ->orderByDesc('id')
            ->first();

        $seq = $last
            ? ((int) substr($last->order_number, -4)) + 1
            : $start;

        return sprintf('%s-%d-%04d', $prefix, $year, $seq);
    }

    public function isAbonnement(): bool
    {
        return $this->type === self::TYPE_ABONNEMENT;
    }

    /** Het abonnement waar deze ophaling onder valt, als die er is. */
    public function subscription()
    {
        return $this->belongsTo(self::class, 'subscription_order_id');
    }

    /** De losse ophalingen die onder dit abonnement zijn ingepland. */
    public function pickups()
    {
        return $this->hasMany(self::class, 'subscription_order_id')->orderBy('pickup_date');
    }

    /**
     * Een ophaling onder een abonnement. Die wordt NIET los gefactureerd: de
     * klant betaalt het abonnement. Zie de guard in Invoice::fromBon().
     */
    public function isSubscriptionPickup(): bool
    {
        return $this->subscription_order_id !== null;
    }

    /**
     * Aantal dagen tussen twee ophalingen. 2x per week heeft geen vast interval
     * (maandag en donderdag liggen niet even ver uit elkaar) en wordt daarom
     * apart afgehandeld in de generator.
     */
    /** Werkdagen waarop opgehaald kan worden, als ISO-weekdag. */
    const PICKUP_WEEKDAYS = [
        1 => 'maandag',
        2 => 'dinsdag',
        3 => 'woensdag',
        4 => 'donderdag',
        5 => 'vrijdag',
    ];

    /**
     * De vaste ophaaldag. Valt terug op de weekdag van de ingangsdatum, zodat een
     * abonnement zonder expliciete keuze zich gedraagt zoals het altijd al deed.
     */
    public function subPickupWeekday(): ?int
    {
        return $this->sub_pickup_weekday ?? $this->sub_active_from?->dayOfWeekIso;
    }

    public function subPickupWeekdayLabel(): string
    {
        if ($this->sub_freq === '2pw') {
            return 'maandag en donderdag';
        }

        $d = $this->subPickupWeekday();

        return self::PICKUP_WEEKDAYS[$d] ?? '—';
    }

    /** 2x per week ligt vast op maandag en donderdag. */
    const TWICE_WEEKLY_ISO = [1, 4];

    /**
     * De eerste ophaaldatum volgens het ritme, vóór verschuiven voor weekend of
     * feestdag. Staat hier en niet in de planner, omdat de activatiemail dezelfde
     * datum moet noemen als de planner aanmaakt. Twee keer uitrekenen betekent
     * vroeg of laat twee verschillende antwoorden.
     */
    public function subFirstScheduledDate(): ?\Carbon\Carbon
    {
        if (! $this->sub_active_from) {
            return null;
        }

        $cursor = $this->sub_active_from->copy()->startOfDay();

        if ($this->sub_freq === '2pw') {
            while (! in_array($cursor->dayOfWeekIso, self::TWICE_WEEKLY_ISO, true)) {
                $cursor->addDay();
            }

            return $cursor;
        }

        $weekday = $this->subPickupWeekday() ?? $cursor->dayOfWeekIso;
        while ($cursor->dayOfWeekIso !== $weekday) {
            $cursor->addDay();
        }

        return $cursor;
    }

    /**
     * De eerstvolgende ophaaldatum. Is er al een ophaling ingepland, dan die,
     * want dat is wat de klant en de chauffeur zien staan. Anders de berekende
     * eerste datum, verschoven als hij op een weekend of feestdag valt.
     */
    public function nextPickupDate(): ?\Carbon\Carbon
    {
        $planned = $this->pickups()
            ->whereDate('pickup_date', '>=', now()->toDateString())
            ->orderBy('pickup_date')
            ->first();

        if ($planned) {
            return $planned->pickup_date;
        }

        $slot = $this->subFirstScheduledDate();

        return $slot ? \App\Support\WorkingDays::next($slot) : null;
    }

    public function subIntervalDays(): ?int
    {
        return match ($this->sub_freq) {
            '4w' => 28,
            '2w' => 14,
            '1w' => 7,
            default => null,
        };
    }

    public function subTermLabel(): string
    {
        return self::SUB_TERM_LABELS[$this->sub_term] ?? ($this->sub_term ?: '—');
    }

    public function subFreqLabel(): string
    {
        return self::SUB_FREQS[$this->sub_freq] ?? ($this->sub_freq ?: '—');
    }

    /**
     * Afgeleide status van een abonnement, net zoals de offertelijst dat doet.
     * Er is bewust geen statuskolom: elke stap heeft al een eigen timestamp, en
     * twee bronnen van waarheid lopen vroeg of laat uit elkaar.
     */
    public function subStatus(): string
    {
        if ($this->hasEnded())        return 'beeindigd';
        if ($this->sub_terminated_at) return 'opgezegd';
        if ($this->sub_active_from)   return 'actief';
        if ($this->quote_accepted_at) return 'geaccepteerd';
        if ($this->quote_sent_at)     return $this->isQuoteExpired() ? 'verlopen' : 'verzonden';

        return 'te beantwoorden';
    }

    /** Opgezegd én de einddatum is voorbij. Tot dan loopt het abonnement door. */
    public function hasEnded(): bool
    {
        return $this->sub_ends_on !== null && $this->sub_ends_on->isPast();
    }

    /** Loopt nu, ongeacht of er al is opgezegd. Bepaalt of we nog factureren. */
    public function isRunning(): bool
    {
        return $this->isAbonnement() && $this->sub_active_from !== null && ! $this->hasEnded();
    }

    /**
     * Minimale looptijd in maanden, zoals gepubliceerd op /rolcontainer-huren.
     * Flex is drie maanden, Vast en Jaarbetaling twaalf. Na die termijn loopt
     * alles maandelijks door en mag er op elk moment worden opgezegd.
     */
    public function subMinimumMonths(): int
    {
        return match ($this->sub_term) {
            'flex' => 3,
            self::SUB_TERM_MONTHLY => 0,
            default => 12,
        };
    }

    /**
     * Laatste dag van de minimumtermijn.
     *
     * Rekenen vanaf de 1e van de startmaand, met NoOverflow. addMonths() op een
     * 31e schuift door naar de volgende maand (31 januari plus drie maanden is
     * 1 mei, niet 30 april), en dat zou een klant die op een maandeinde start
     * een hele maand langer vastzetten.
     */
    public function minimumTermEnd(): ?\Carbon\Carbon
    {
        $anchor = $this->subTermAnchor();

        if (! $anchor) {
            return null;
        }

        return $anchor->copy()
            ->startOfMonth()
            ->addMonthsNoOverflow($this->subMinimumMonths())
            ->endOfMonth()
            ->startOfDay();
    }

    /**
     * Startpunt van de lopende termijn. Bij een verlenging schuift dit door, dus
     * hiervandaan rekenen en niet vanaf sub_active_from. Oudere rijen hebben nog
     * geen anker en vallen terug op de activatiedatum.
     */
    public function subTermAnchor(): ?\Carbon\Carbon
    {
        return $this->sub_term_started_on ?? $this->sub_active_from;
    }

    /**
     * Einde van de factuurperiode die op $start begint.
     *
     * Alles ligt op maandgrenzen, ook de jaarperiode. Een jaar dat op 15 juli
     * begint loopt dus tot en met 31 juli van het jaar erop, gelijk met het
     * einde van de contracttermijn. Zonder die uitlijning zou er na een
     * jaarperiode een restje van een halve maand overblijven, en dat restje zou
     * als losse periode gefactureerd worden.
     */
    public function subPeriodEnd(\Carbon\Carbon $start): \Carbon\Carbon
    {
        if ($this->sub_term === 'jaar') {
            return $start->copy()->startOfMonth()->addMonthsNoOverflow(12)->endOfMonth()->startOfDay();
        }

        return $start->copy()->endOfMonth()->startOfDay();
    }

    /**
     * Lengte van een volle periode in dagen, gerekend vanaf de 1e van de
     * startmaand. Dit is de noemer voor een deelperiode. Een start op de 15e
     * moet 17 van 31 dagen betalen, dus de noemer is de hele maand en niet het
     * stuk dat we toevallig factureren.
     */
    public function subPeriodNominalDays(\Carbon\Carbon $start): int
    {
        if ($this->sub_term === 'jaar') {
            $from = $start->copy()->startOfMonth();

            return $from->diffInDays($this->subPeriodEnd($start)) + 1;
        }

        return $start->daysInMonth;
    }

    /**
     * Datum waarop de lopende contracttermijn afloopt. Alleen Vast en Jaar
     * hebben er een. Flex en de losse maandtermijn lopen gewoon door, daar valt
     * niets te verlengen.
     */
    public function subRenewalDate(): ?\Carbon\Carbon
    {
        return in_array($this->sub_term, ['vast', 'jaar'], true) ? $this->minimumTermEnd() : null;
    }

    /**
     * Zet een afgelopen Vast of Jaar om naar de losse maandtermijn, tegen het
     * maandtarief van Vast. Dat is wat de site belooft na twaalf maanden, en het
     * is ook de eerlijke prijs: de klant heeft die twaalf maanden gedraaid.
     * Terugzetten naar 'vast' zou hem stilzwijgend een nieuwe termijn in duwen.
     */
    public function convertToMonthly(): void
    {
        $renewal = $this->subRenewalDate();

        $this->update([
            'sub_term'                => self::SUB_TERM_MONTHLY,
            'sub_price_excl_btw'      => config("desnipperaar.subscription.prices.vast.{$this->sub_freq}")
                                          ?? $this->sub_price_excl_btw,
            'sub_term_started_on'     => $renewal ? $renewal->copy()->addDay()->toDateString() : null,
            'sub_renewal_notified_at' => null,
        ]);
    }

    /**
     * De vroegste datum waarop dit abonnement mag stoppen.
     *
     * Facturatie loopt per kalendermaand, dus een opzegging landt altijd op de
     * laatste dag van een maand. Twee grenzen tellen, en de laatste wint: de
     * lopende maand moet af, en de minimumtermijn moet voorbij zijn. Er is geen
     * opzegtermijn, want die staat nergens op de site en dan mogen we hem ook
     * niet rekenen.
     */
    public function earliestTerminationDate(?\Carbon\Carbon $on = null): \Carbon\Carbon
    {
        $on = $on ? $on->copy() : now();

        $endOfThisMonth = $on->copy()->endOfMonth()->startOfDay();
        $minimumTermEnd = $this->minimumTermEnd();

        if (! $minimumTermEnd) {
            return $endOfThisMonth;
        }

        return $minimumTermEnd->greaterThan($endOfThisMonth) ? $minimumTermEnd : $endOfThisMonth;
    }

    /**
     * € 75 logistieke retourkosten, alleen bij Flex dat binnen twaalf maanden
     * stopt. Gepubliceerd als "geen boete, alleen de werkelijke rit", dus het
     * hoort als eigen regel op de slotfactuur te staan, niet verstopt in het
     * maandbedrag.
     */
    public function owesReturnCost(): bool
    {
        if ($this->sub_term !== 'flex' || ! $this->sub_active_from || ! $this->sub_ends_on) {
            return false;
        }

        return $this->sub_ends_on->lessThan($this->sub_active_from->copy()->addMonthsNoOverflow(12));
    }

    public static function generateSubscriptionReference(): string
    {
        $prefix = config('desnipperaar.order.sub_prefix');
        $year   = now()->year;
        $start  = config('desnipperaar.order.start');

        $last = self::where('quote_reference', 'like', "{$prefix}-{$year}-%")
            ->orderByDesc('id')
            ->first();

        $seq = $last
            ? ((int) substr($last->quote_reference, -4)) + 1
            : $start;

        return sprintf('%s-%d-%04d', $prefix, $year, $seq);
    }

    public static function generateQuoteReference(): string
    {
        $prefix = config('desnipperaar.order.quote_prefix');
        $year   = now()->year;
        $start  = config('desnipperaar.order.start');

        $last = self::where('quote_reference', 'like', "{$prefix}-{$year}-%")
            ->orderByDesc('id')
            ->first();

        $seq = $last
            ? ((int) substr($last->quote_reference, -4)) + 1
            : $start;

        return sprintf('%s-%d-%04d', $prefix, $year, $seq);
    }
}
