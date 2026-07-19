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
     * Wij brengen een container naar de klant. Een eigen type en geen
     * delivery_mode-waarde, want 'breng' betekent hier al de brengservice,
     * waarbij de KLANT materiaal bij ons brengt. Dat is de andere richting, en
     * het stond ook zo op de bon afgedrukt.
     */
    const TYPE_BEZORGING     = 'bezorging';

    /** delivery_mode: hoe het materiaal bij ons komt, niet hoe de container komt. */
    const DELIVERY_BRENG  = 'breng';   // klant brengt zelf (brengservice)
    const DELIVERY_OPHAAL = 'ophaal';

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
        self::SUB_TERM_MONTHLY => 'Maandelijks (na de eerste termijn, altijd opzegbaar, voordeeltarief)',
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
        'pickup_reminder_sent_at',
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
        'pickup_reminder_sent_at' => 'datetime',
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

    /**
     * Bij een abonnement hoort per vernietiging een certificaat, dus meerdere.
     * Een losse order heeft er hooguit één; certificate() blijft daarvoor
     * bestaan zodat bestaande schermen niet hoeven te weten welk geval het is.
     */
    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    public function certificate()
    {
        return $this->hasOne(Certificate::class)->latestOfMany();
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
        return \App\Support\NumberSequence::next(config('desnipperaar.order.prefix'), (int) config('desnipperaar.order.start'));
    }

    public function isAbonnement(): bool
    {
        return $this->type === self::TYPE_ABONNEMENT;
    }

    /** Alle ritten onder dit abonnement: bezorging, ophalingen en retour. */
    public function visits()
    {
        return $this->hasMany(Bon::class)->orderBy('planned_for');
    }

    /**
     * Alleen de ophalingen. De bezorgrit hoort er bewust niet bij: die telt niet
     * mee in het ritme, mag niet verdwijnen als de ophaaldag wijzigt, en een
     * herinnering erover moet andere tekst hebben dan "wij halen morgen op".
     */
    public function pickups()
    {
        return $this->hasMany(Bon::class)
            ->where('mode', Bon::MODE_OPHAAL)
            ->orderBy('planned_for');
    }

    /** De rit waarmee de container wordt gebracht. Er is er hooguit één. */
    public function deliveryVisit()
    {
        return $this->hasOne(Bon::class)->where('mode', Bon::MODE_BEZORGING);
    }

    public function retourVisit()
    {
        return $this->hasOne(Bon::class)->where('mode', Bon::MODE_RETOUR);
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
        $delivery = $this->subDeliveryDate();

        if (! $delivery) {
            return null;
        }

        // Eerst brengen, dan pas halen. De eerste ophaling is de bezorgdag plus
        // precies één frequentie, dus bij 1x per 2 weken staat de container er
        // twee weken voordat wij hem voor het eerst legen. Zo heeft de klant een
        // volle cyclus om te vullen. Meteen na het brengen ophalen zou een lege
        // container ophalen zijn.
        //
        // Er zit geen vaste wachttijd in de formule: de bezorgdag is een keuze
        // bij het goedkeuren, de rest volgt eruit.
        if ($this->sub_freq === '2pw') {
            // Twee keer per week vult snel; de eerstvolgende vaste dag na de
            // bezorging is genoeg wachttijd.
            $cursor = $delivery->copy()->addDay();
            while (! in_array($cursor->dayOfWeekIso, self::TWICE_WEEKLY_ISO, true)) {
                $cursor->addDay();
            }

            return $cursor;
        }

        $interval = $this->subIntervalDays();
        if (! $interval) {
            return null;
        }

        $cursor = $delivery->copy()->addDays($interval);

        // De bezorgdag ligt al op de afgesproken weekdag en elk interval is een
        // veelvoud van zeven, dus dit klopt meestal meteen. De lus is er voor
        // oudere rijen waar dat niet zo is.
        $weekday = $this->subPickupWeekday() ?? $cursor->dayOfWeekIso;
        while ($cursor->dayOfWeekIso !== $weekday) {
            $cursor->addDay();
        }

        return $cursor;
    }

    /**
     * De dag waarop wij de container brengen. Dat is ook de dag waarop het
     * contract en de facturatie beginnen: vanaf dat moment staat de container
     * bij de klant. sub_active_from is die datum, deze methode is er om dat in
     * de code leesbaar te houden.
     */
    public function subDeliveryDate(): ?\Carbon\Carbon
    {
        return $this->sub_active_from?->copy()->startOfDay();
    }

    /**
     * De eerstvolgende ophaaldatum. Is er al een ophaling ingepland, dan die,
     * want dat is wat de klant en de chauffeur zien staan. Anders de berekende
     * eerste datum, verschoven als hij op een weekend of feestdag valt.
     */
    public function nextPickupDate(): ?\Carbon\Carbon
    {
        $planned = $this->pickups()
            ->whereDate('planned_for', '>=', now()->toDateString())
            ->orderBy('planned_for')
            ->first();

        if ($planned) {
            return $planned->planned_for;
        }

        $slot = $this->subFirstScheduledDate();

        // Dezelfde verschuifregel als de planner, anders noemt de mail een datum
        // waarop geen rit wordt aangemaakt.
        return $slot ? \App\Support\WorkingDays::nextSameWeekday($slot) : null;
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

    /**
     * Mag dit abonnement terug naar "aanvraag"?
     *
     * Alleen zolang er niets onomkeerbaars is gebeurd. Is er al een ophaling
     * gereden (die heeft een bon) of is er al een factuur uit, dan zou
     * terugzetten administratie wissen die ergens anders al is gebruikt. Dan is
     * opzeggen de juiste weg, niet doen alsof het nooit heeft gelopen.
     */
    public function canResetToPending(): bool
    {
        if (! $this->isAbonnement() || ! $this->sub_active_from) {
            return false;
        }

        if ($this->invoices()->whereNotNull('period_start')->exists()) {
            return false;
        }

        // Kijken naar een GETEKENDE bon, niet naar het bestaan van een bon. Een
        // bon wordt al aangemaakt zodra er een chauffeur wordt toegewezen, soms
        // weken voor de rit. Dat blokkeerde het terugzetten van een abonnement
        // waar nog niets voor was gereden.
        //
        // picked_up_at is hetzelfde signaal dat CertificateController gebruikt om
        // te bepalen of er echt iets is gebeurd. Ook de bezorgrit telt mee: is
        // die getekend, dan staat de container bij de klant en is "nooit
        // geactiveerd" niet meer waar.
        return ! $this->bons()->whereNotNull('picked_up_at')->exists();
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

    /** Aantal weken in één factuurperiode. Flex en Vast per 4 weken, Jaar per 52. */
    public function subPeriodWeeks(): int
    {
        return $this->sub_term === 'jaar' ? 52 : 4;
    }

    /**
     * Einde van de factuurperiode die op $start begint.
     *
     * De prijs is per 4 weken, niet per kalendermaand: zo adverteren wij het en
     * zo is de prijs gezet. Een periode is dus precies 28 dagen (52 weken bij
     * jaarbetaling), geteld vanaf de bezorgdag. Geen maandgrenzen, en daardoor
     * geen deelperiode aan het begin: de eerste periode start op de bezorgdag en
     * loopt vol.
     */
    public function subPeriodEnd(\Carbon\Carbon $start): \Carbon\Carbon
    {
        return $start->copy()->addWeeks($this->subPeriodWeeks())->subDay()->startOfDay();
    }

    /**
     * Lengte van een volle periode in dagen. De noemer voor een deelperiode.
     * Die deelperiode ontstaat alleen nog bij opzeggen midden in een periode,
     * niet meer aan het begin, want de periode begint op de bezorgdag.
     */
    public function subPeriodNominalDays(\Carbon\Carbon $start): int
    {
        return $this->subPeriodWeeks() * 7;
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
        return \App\Support\NumberSequence::next(config('desnipperaar.order.sub_prefix'), (int) config('desnipperaar.order.start'));
    }

    public static function generateQuoteReference(): string
    {
        return \App\Support\NumberSequence::next(config('desnipperaar.order.quote_prefix'), (int) config('desnipperaar.order.start'));
    }
}
