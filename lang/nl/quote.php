<?php

/*
 * Teksten van de publieke offertepagina op /offerte/{token}.
 *
 * De pagina was Nederlands, ook voor een klant die zijn offerte in het Engels of
 * Frans kreeg. De taal komt uit orders.locale, dezelfde bron die de mails al
 * gebruiken, en wordt in QuoteAcceptController gezet.
 *
 * Bedragen en datums blijven in Nederlandse notatie, in alle talen. Dat is geen
 * omissie. De factuur die erop volgt gebruikt diezelfde notatie en twee
 * verschillende schrijfwijzen voor hetzelfde bedrag is verwarrender dan een
 * punt op de verkeerde plek.
 */

return [

    'title'        => 'Offerte :number',
    'eyebrow'      => 'Offerte',
    'h1'           => 'Uw offerte op maat',

    'banner_accepted' => 'Deze offerte is al geaccepteerd op :date. Een orderbevestiging is onderweg.',
    'banner_expired'  => 'Deze offerte is verlopen op :date. Neem contact op voor een nieuwe offerte.',

    'scope_h'      => 'Scope en prijs',
    'th_desc'      => 'Omschrijving',
    'th_qty'       => 'Aantal',
    'th_price'     => 'Prijs',
    'th_subtotal'  => 'Subtotaal',

    'options_h'    => 'Extra opties',
    'options_help' => 'Vink aan wat u wilt toevoegen. Het totaal past zich direct aan.',

    'amount_excl'  => 'Bedrag excl. btw',
    'vat'          => 'BTW 21%',
    'incl_vat'     => 'incl. btw',
    'valid_until'  => 'Deze offerte is geldig tot :date.',

    'details_h'    => 'Uw gegevens',
    'details_help' => 'Vul het adres in waar wij de opdracht uitvoeren. Daarna plaatst u de opdracht.',

    'f_name'       => 'Naam',
    'f_email'      => 'E-mailadres',
    'f_company'    => 'Bedrijf',
    'f_optional'   => '(optioneel)',
    'f_phone'      => 'Telefoon',
    'f_street'     => 'Straatnaam',
    'f_number'     => 'Huisnummer',
    'f_postcode'   => 'Postcode',
    'f_city'       => 'Stad',

    'submit'       => 'Plaats opdracht',
    'legal'        => 'Door op <strong>:button</strong> te klikken gaat u akkoord met het bedrag van <strong>:amount</strong> incl. btw en de <a href=":terms" target="_blank" style="color:#0A0A0A;">algemene voorwaarden</a>. Uw IP-adres en tijdstip worden vastgelegd als bewijs.',

    'modal_h'      => 'Opdracht plaatsen?',
    'modal_p'      => 'U plaatst nu een opdracht op basis van offerte <strong style="font-family:monospace;">:number</strong>. Dit is een bindende opdracht voor <strong>:amount</strong> incl. btw.',
    'modal_cancel' => 'Annuleer',
    'modal_ok'     => 'Ja, plaats opdracht',

    'trust'        => 'AVG &middot; DIN 66399 &middot; VOG &middot; Verzekerd &middot; &euro;&nbsp;2,5 mln dekking',

    // Bevestigingspagina na het plaatsen
    'ok_title'          => 'Offerte geaccepteerd',
    'ok_h1'             => 'Bedankt, uw opdracht is geplaatst.',
    'ok_p'              => 'Uw offerte voor :number is geaccepteerd.',
    'ok_mail'           => 'Een orderbevestiging is per e-mail verstuurd naar :email.',
    'ok_amount'         => 'Bedrag incl. btw',
    'ok_accepted_at'    => 'Geaccepteerd op',
    'ok_next'           => 'Binnen een werkdag nemen wij contact met u op om de uitvoering in te plannen.',

    'sub_title'         => 'Abonnement bevestigd',
    'sub_h1'            => 'Bedankt, uw abonnement loopt.',
    'sub_p'             => 'Uw abonnement :number is bevestigd.',
    'sub_mail'          => 'Een bevestiging is per e-mail verstuurd naar :email.',
    'sub_freq'          => 'Frequentie',
    'sub_term'          => 'Looptijd',
    'sub_price'         => 'Prijs incl. btw',
    'sub_per_year'      => 'per jaar',
    'sub_per_4w'        => 'per 4 weken',
    'sub_confirmed_at'  => 'Bevestigd op',
    'sub_next'          => 'Binnen een werkdag nemen wij contact met u op om de container te plaatsen en het eerste ophaalmoment af te spreken.',

    // Offerte was al geaccepteerd
    'done_title'   => 'Offerte al geaccepteerd',
    'done_banner'  => 'Deze offerte is al geaccepteerd op :date.',
    'done_h1'      => 'Uw opdracht is al geplaatst.',
    'done_p'       => 'Order :number is in behandeling. U heeft de orderbevestiging per e-mail ontvangen op :email.',
    'done_small'   => 'Geen mail ontvangen? Kijk in uw spam of neem contact op via :phone.',

    // Ingetrokken
    'cancel_title'  => 'Offerte geannuleerd',
    'cancel_banner' => 'Deze offerte is geannuleerd.',
    'cancel_banner_date' => 'Deze offerte is geannuleerd op :date.',
    'cancel_h1'     => 'Offerte niet meer geldig.',
    'cancel_p'      => 'Offerte :number is ingetrokken en kan niet meer worden geaccepteerd.',
    'cancel_reason' => 'De reden die wij erbij noteerden is ":reason".',
    'cancel_new'    => 'Gaat het toch door? Neem contact op via :phone of :email voor een nieuwe offerte.',

    // Verlopen
    'exp_title'   => 'Offerte verlopen',
    'exp_banner'  => 'Deze offerte is verlopen op :date.',
    'exp_h1'      => 'Offerte niet meer geldig.',
    'exp_p'       => 'De geldigheid van offerte :number is verstreken.',
    'exp_new'     => 'Neem contact op via :phone of :email voor een nieuwe offerte.',

    'validation' => [
        'name'       => 'Vul uw naam in.',
        'email'      => 'Vul uw e-mailadres in.',
        'email_bad'  => 'Vul een geldig e-mailadres in.',
        'phone'      => 'Vul uw telefoonnummer in.',
        'street'     => 'Vul de straatnaam in.',
        'number'     => 'Vul het huisnummer in.',
        'postcode'   => 'Vul uw postcode in.',
        'postcode_bad' => 'Vul een geldige postcode in, bijvoorbeeld 1034AB.',
        'city'       => 'Vul de stad in.',
    ],

];
