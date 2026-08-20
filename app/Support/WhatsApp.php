<?php

namespace App\Support;

use App\Models\Order;

/**
 * WhatsApp-berichten bij een order.
 *
 * Wij versturen niet zelf. De knop opent wa.me met een voorgevuld bericht in de
 * WhatsApp van degene die op de knop drukt, en die drukt daar op verzenden. Dat
 * scheelt een geverifieerd WhatsApp Business-account met door Meta goedgekeurde
 * templates, en het bericht komt van het nummer dat de klant toch al kent.
 *
 * Gevolg: wij weten niet of het bericht daarna echt de deur uit is gegaan. De
 * regel in Berichten zegt daarom "klaargezet", niet "verzonden". Wie dat wel
 * zeker wil weten heeft de Cloud API nodig, en dan kan deze klasse blijven
 * staan met een echte verzendmethode ernaast.
 */
class WhatsApp
{
    /** Landcode zonder plus voor nummers die zonder landcode zijn ingevuld. */
    private const DEFAULT_COUNTRY = '31';

    /**
     * De namen in het uitklapmenu. Die blijven Nederlands ook als het bericht
     * Frans is, want de admin is Nederlands en jij kiest hier, niet de klant.
     */
    private const LABELS = [
        'onderweg'    => 'Wij komen vandaag',
        'bevestigd'   => 'Ophaalmoment bevestigd',
        'offerte'     => 'Offerte nabellen',
        'plannen'     => 'Zelf een moment kiezen',
        'aanvraag'    => 'Aanvraag ontvangen',
        'opgehaald'   => 'Opgehaald',
        'certificaat' => 'Vernietigd, certificaat verstuurd',
        'vrij'        => 'Eigen bericht',
    ];

    /**
     * Naar het formaat dat wa.me wil: alleen cijfers, met landcode, zonder plus
     * en zonder nul ervoor.
     *
     * Klanten typen van alles: "06-12345678", "+31 (0)6 12 34 56 78",
     * "0031612345678". Alles wat geen cijfer is gaat eruit, de nul van de
     * netcode gaat eruit zodra er een landcode voor staat.
     *
     * Geeft null terug als er geen bruikbaar nummer in zit, want een wa.me-link
     * naar een half nummer opent een lege chat en dat is erger dan geen knop.
     */
    public static function normalize(?string $phone, string $country = self::DEFAULT_COUNTRY): ?string
    {
        if (! $phone) {
            return null;
        }

        // "+31 (0)6 ..." is Nederlandse notatie voor "kies de 0 alleen binnenlands".
        // Die nul hoort er internationaal niet bij, dus haal hem weg voordat hij
        // hieronder als deel van het nummer wordt gelezen.
        $raw    = preg_replace('/\(\s*0\s*\)/', '', trim($phone));
        $plus   = str_starts_with($raw, '+');
        $digits = preg_replace('/\D+/', '', $raw);

        if ($digits === '') {
            return null;
        }

        if ($plus) {
            $number = $digits;
        } elseif (str_starts_with($digits, '00')) {
            $number = substr($digits, 2);
        } elseif (str_starts_with($digits, '0')) {
            $number = $country.substr($digits, 1);
        } elseif (str_starts_with($digits, $country)) {
            $number = $digits;
        } else {
            $number = $country.$digits;
        }

        $number = self::stripTrunkZero($number, $country);

        // Kortste landnummers zijn zeven cijfers inclusief landcode, E.164 stopt
        // bij vijftien. Wat daarbuiten valt is een typefout of een intern toestel.
        return strlen($number) >= 8 && strlen($number) <= 15 ? $number : null;
    }

    /**
     * "+31 0612345678" haalt de nul niet weg met de haakjesregel hierboven.
     * Een Nederlands nummer is negen cijfers na de landcode, dus alles wat
     * langer is en met een nul begint heeft er een te veel.
     */
    private static function stripTrunkZero(string $number, string $country): string
    {
        $rest = substr($number, strlen($country));

        if (str_starts_with($number, $country) && str_starts_with($rest, '0') && strlen($rest) > 9) {
            return $country.ltrim($rest, '0');
        }

        return $number;
    }

    /** Leesbare weergave van een genormaliseerd nummer, bijvoorbeeld +31612345678. */
    public static function display(string $number): string
    {
        return '+'.$number;
    }

    /**
     * wa.me en niet api.whatsapp.com: die eerste stuurt door naar de app als die
     * geïnstalleerd is, en anders naar WhatsApp Web.
     */
    public static function url(string $number, string $text): string
    {
        return 'https://wa.me/'.$number.'?text='.rawurlencode($text);
    }

    /**
     * De sjablonen die bij deze order horen, de meest waarschijnlijke voorop.
     *
     * Bewust hier en niet in blade-views zoals de mails: dit zijn vier regels
     * platte tekst per stuk, en ze moeten als string in een URL passen.
     */
    public static function templates(Order $order): array
    {
        $locale = self::locale($order);
        $lines  = self::lines($locale);

        $vars = [
            '{naam}'   => self::firstName($order->customer_name),
            '{nummer}' => (string) $order->order_number,
            '{datum}'  => self::dateLabel($order, $locale),
            '{venster}'=> self::windowLabel($order, $locale),
            '{link}'   => self::planUrl($order),
            '{ik}'     => self::senderName($order),
        ];

        $out = [];
        foreach (self::relevantKeys($order) as $key) {
            $out[] = [
                'key'   => $key,
                'label' => self::LABELS[$key],
                'text'  => trim(strtr($lines[$key] ?? '', $vars)),
            ];
        }

        return $out;
    }

    /**
     * Welke sjablonen zinnig zijn bij deze order.
     *
     * Een planlink zonder token of een bevestiging zonder datum levert een
     * bericht met een gat erin op, dus die vallen weg. De volgorde volgt de
     * reis van de order, zodat het eerste sjabloon in de lijst meestal het
     * bedoelde is.
     */
    private static function relevantKeys(Order $order): array
    {
        $keys = [];

        if ($order->pickup_date && $order->pickup_date->isToday()) {
            $keys[] = 'onderweg';
        }
        if ($order->pickup_date) {
            $keys[] = 'bevestigd';
        }
        if ($order->type === Order::TYPE_QUOTE && $order->quote_sent_at && ! $order->quote_accepted_at) {
            $keys[] = 'offerte';
        }
        // Een planlink heeft alleen zin zolang er nog iets te plannen valt.
        // Staat de order al op opgehaald, dan is een uitnodiging om een moment
        // te kiezen verwarrend, ook al ontbreekt de datum in de administratie.
        if (! $order->pickup_date && $order->public_token && ! $order->isPickedUp() && ! $order->isCanceled()) {
            $keys[] = 'plannen';
        }
        if ($order->state === Order::STATE_NIEUW) {
            $keys[] = 'aanvraag';
        }
        if (in_array($order->state, [Order::STATE_OPGEHAALD], true)) {
            $keys[] = 'opgehaald';
        }
        if (in_array($order->state, [Order::STATE_VERNIETIGD, Order::STATE_AFGESLOTEN], true)) {
            $keys[] = 'certificaat';
        }

        $keys[] = 'vrij';

        return array_values(array_unique($keys));
    }

    private static function locale(Order $order): string
    {
        return in_array($order->locale, ['nl', 'en', 'fr', 'es'], true) ? $order->locale : 'nl';
    }

    private static function firstName(?string $name): string
    {
        return trim(explode(' ', trim((string) $name))[0]);
    }

    private static function senderName(Order $order): string
    {
        return self::firstName($order->senderUser()?->name) ?: 'DeSnipperaar';
    }

    private static function dateLabel(Order $order, string $locale): string
    {
        return $order->pickup_date
            ? $order->pickup_date->locale($locale)->translatedFormat('l j F')
            : '';
    }

    /**
     * Een tijdvak als 09:00-11:00 blijft staan zoals het is. Een dagdeel krijgt
     * de vertaling erbij, want "ochtend" zegt een Franse klant niets.
     */
    private static function windowLabel(Order $order, string $locale): string
    {
        $window = $order->pickup_window ?: 'flexibel';

        if (preg_match('/^\d{2}:00-\d{2}:00$/', $window)) {
            return $window;
        }

        $labels = [
            'nl' => ['ochtend' => 'ochtend',   'middag' => 'middag',       'avond' => 'avond',   'flexibel' => 'in overleg'],
            'en' => ['ochtend' => 'morning',   'middag' => 'afternoon',    'avond' => 'evening', 'flexibel' => 'to be agreed'],
            'fr' => ['ochtend' => 'matin',     'middag' => 'après-midi',   'avond' => 'soir',    'flexibel' => 'à convenir'],
            'es' => ['ochtend' => 'mañana',    'middag' => 'tarde',        'avond' => 'noche',   'flexibel' => 'a convenir'],
        ];

        return $labels[$locale][$window] ?? $labels['nl'][$window] ?? $window;
    }

    /**
     * De planpagina bestaat in het Nederlands en het Engels, net als in de
     * mails. Frans en Spaans krijgen de Nederlandse pagina, want een pagina die
     * bestaat is beter dan een 404.
     */
    private static function planUrl(Order $order): string
    {
        if (! $order->public_token) {
            return '';
        }

        $prefix = self::locale($order) === 'en' ? '/en' : '';

        return PublicUrl::base().$prefix.'/plan/'.$order->public_token;
    }

    /**
     * De teksten zelf. Kort, geen dubbele punt en geen puntkomma, want dit is
     * een appje en geen brief.
     */
    private static function lines(string $locale): array
    {
        $all = [
            'nl' => [
                'onderweg' => "Hallo {naam},\n\nWij komen vandaag langs voor opdracht {nummer}. Het tijdvak is {venster}.\n\nZet u alvast klaar wat mee moet?\n\nGroet,\n{ik} van DeSnipperaar",
                'bevestigd' => "Hallo {naam},\n\nHet ophaalmoment voor opdracht {nummer} staat op {datum}, tijdvak {venster}.\n\nLukt dat moment niet, laat het gerust weten.\n\nGroet,\n{ik} van DeSnipperaar",
                'offerte' => "Hallo {naam},\n\nWij hebben u een offerte gestuurd voor aanvraag {nummer}. Is die goed aangekomen?\n\nLaat gerust weten of er iets aangepast moet worden.\n\nGroet,\n{ik} van DeSnipperaar",
                'plannen' => "Hallo {naam},\n\nVoor opdracht {nummer} kunt u zelf een ophaalmoment kiezen.\n{link}\n\nGroet,\n{ik} van DeSnipperaar",
                'aanvraag' => "Hallo {naam},\n\nWij hebben uw aanvraag {nummer} in goede orde ontvangen. Wij plannen een ophaalmoment in en laten het u weten.\n\nHeeft u vragen, stel ze gerust hier.\n\nGroet,\n{ik} van DeSnipperaar",
                'opgehaald' => "Hallo {naam},\n\nWij hebben alles voor opdracht {nummer} opgehaald. Zodra het vernietigd is sturen wij het certificaat per mail.\n\nGroet,\n{ik} van DeSnipperaar",
                'certificaat' => "Hallo {naam},\n\nAlles van opdracht {nummer} is vernietigd. Het certificaat staat in uw mail.\n\nBedankt voor de opdracht.\n\nGroet,\n{ik} van DeSnipperaar",
                'vrij' => '',
            ],
            'en' => [
                'onderweg' => "Hello {naam},\n\nWe are coming round today for order {nummer}. The time slot is {venster}.\n\nCould you put everything ready for us?\n\nKind regards,\n{ik} at DeSnipperaar",
                'bevestigd' => "Hello {naam},\n\nThe pickup for order {nummer} is set for {datum}, time slot {venster}.\n\nIf that does not work for you, just let us know.\n\nKind regards,\n{ik} at DeSnipperaar",
                'offerte' => "Hello {naam},\n\nWe sent you a quote for request {nummer}. Did it arrive safely?\n\nLet us know if anything needs changing.\n\nKind regards,\n{ik} at DeSnipperaar",
                'plannen' => "Hello {naam},\n\nYou can pick your own pickup slot for order {nummer}.\n{link}\n\nKind regards,\n{ik} at DeSnipperaar",
                'aanvraag' => "Hello {naam},\n\nWe have received your request {nummer}. We will schedule a pickup and let you know.\n\nIf you have any questions, just ask here.\n\nKind regards,\n{ik} at DeSnipperaar",
                'opgehaald' => "Hello {naam},\n\nWe have collected everything for order {nummer}. As soon as it has been destroyed we will email you the certificate.\n\nKind regards,\n{ik} at DeSnipperaar",
                'certificaat' => "Hello {naam},\n\nEverything from order {nummer} has been destroyed. The certificate is in your inbox.\n\nThank you for your business.\n\nKind regards,\n{ik} at DeSnipperaar",
                'vrij' => '',
            ],
            'fr' => [
                'onderweg' => "Bonjour {naam},\n\nNous passons aujourd'hui pour la commande {nummer}. Le créneau est {venster}.\n\nPouvez-vous tout préparer pour nous ?\n\nCordialement,\n{ik} de DeSnipperaar",
                'bevestigd' => "Bonjour {naam},\n\nL'enlèvement pour la commande {nummer} est prévu le {datum}, créneau {venster}.\n\nSi cela ne vous convient pas, dites-le nous.\n\nCordialement,\n{ik} de DeSnipperaar",
                'offerte' => "Bonjour {naam},\n\nNous vous avons envoyé un devis pour la demande {nummer}. L'avez-vous bien reçu ?\n\nDites-nous si quelque chose doit être adapté.\n\nCordialement,\n{ik} de DeSnipperaar",
                'plannen' => "Bonjour {naam},\n\nVous pouvez choisir vous-même le moment de l'enlèvement pour la commande {nummer}.\n{link}\n\nCordialement,\n{ik} de DeSnipperaar",
                'aanvraag' => "Bonjour {naam},\n\nNous avons bien reçu votre demande {nummer}. Nous planifions un enlèvement et nous vous tenons au courant.\n\nSi vous avez des questions, posez-les ici.\n\nCordialement,\n{ik} de DeSnipperaar",
                'opgehaald' => "Bonjour {naam},\n\nNous avons enlevé le tout pour la commande {nummer}. Dès que la destruction est faite, nous vous envoyons le certificat par e-mail.\n\nCordialement,\n{ik} de DeSnipperaar",
                'certificaat' => "Bonjour {naam},\n\nTout ce qui relève de la commande {nummer} a été détruit. Le certificat se trouve dans votre boîte mail.\n\nMerci de votre confiance.\n\nCordialement,\n{ik} de DeSnipperaar",
                'vrij' => '',
            ],
            'es' => [
                'onderweg' => "Hola {naam},\n\nHoy pasamos por el pedido {nummer}. La franja horaria es {venster}.\n\n¿Puede dejarlo todo preparado?\n\nUn saludo,\n{ik} de DeSnipperaar",
                'bevestigd' => "Hola {naam},\n\nLa recogida del pedido {nummer} está prevista para el {datum}, franja {venster}.\n\nSi no le viene bien, díganoslo sin problema.\n\nUn saludo,\n{ik} de DeSnipperaar",
                'offerte' => "Hola {naam},\n\nLe enviamos un presupuesto para la solicitud {nummer}. ¿Le ha llegado bien?\n\nDíganos si hay que cambiar algo.\n\nUn saludo,\n{ik} de DeSnipperaar",
                'plannen' => "Hola {naam},\n\nPuede elegir usted mismo el momento de recogida del pedido {nummer}.\n{link}\n\nUn saludo,\n{ik} de DeSnipperaar",
                'aanvraag' => "Hola {naam},\n\nHemos recibido su solicitud {nummer}. Vamos a planificar una recogida y le avisamos.\n\nSi tiene alguna duda, pregúntenos aquí.\n\nUn saludo,\n{ik} de DeSnipperaar",
                'opgehaald' => "Hola {naam},\n\nHemos recogido todo el pedido {nummer}. En cuanto esté destruido le enviamos el certificado por correo.\n\nUn saludo,\n{ik} de DeSnipperaar",
                'certificaat' => "Hola {naam},\n\nTodo el pedido {nummer} ha sido destruido. El certificado está en su correo.\n\nGracias por confiar en nosotros.\n\nUn saludo,\n{ik} de DeSnipperaar",
                'vrij' => '',
            ],
        ];

        return $all[$locale] ?? $all['nl'];
    }
}
