<?php

// Textes français de la page de devis publique. Voir lang/nl/quote.php pour la
// raison pour laquelle les montants et les dates gardent le format néerlandais.

return [

    'title'        => 'Devis :number',
    'eyebrow'      => 'Devis',
    'h1'           => 'Votre devis sur mesure',

    'banner_accepted' => 'Ce devis a déjà été accepté le :date. Une confirmation de commande est en route.',
    'banner_expired'  => 'Ce devis a expiré le :date. Contactez-nous pour en obtenir un nouveau.',

    'scope_h'      => 'Prestation et prix',
    'th_desc'      => 'Description',
    'th_qty'       => 'Quantité',
    'th_price'     => 'Prix',
    'th_subtotal'  => 'Sous-total',

    'options_h'    => 'Options supplémentaires',
    'options_help' => 'Cochez ce que vous souhaitez ajouter. Le total se met à jour immédiatement.',

    'amount_excl'  => 'Montant HT',
    'vat'          => 'TVA 21%',
    'incl_vat'     => 'TTC',
    'valid_until'  => 'Ce devis est valable jusqu\'au :date.',

    'details_h'    => 'Vos coordonnées',
    'details_help' => 'Indiquez l\'adresse où nous exécutons la mission. Vous passez ensuite la commande.',

    'f_name'       => 'Nom',
    'f_email'      => 'Adresse e-mail',
    'f_company'    => 'Société',
    'f_optional'   => '(facultatif)',
    'f_phone'      => 'Téléphone',
    'f_street'     => 'Rue',
    'f_number'     => 'Numéro',
    'f_postcode'   => 'Code postal',
    'f_city'       => 'Ville',

    'submit'       => 'Passer commande',
    'legal'        => 'En cliquant sur <strong>:button</strong>, vous acceptez le montant de <strong>:amount</strong> TTC et nos <a href=":terms" target="_blank" style="color:#0A0A0A;">conditions générales</a>. Votre adresse IP et l\'heure sont enregistrées à titre de preuve.',

    'modal_h'      => 'Passer la commande?',
    'modal_p'      => 'Vous passez une commande sur la base du devis <strong style="font-family:monospace;">:number</strong>. Il s\'agit d\'une commande ferme de <strong>:amount</strong> TTC.',
    'modal_cancel' => 'Annuler',
    'modal_ok'     => 'Oui, passer commande',

    'trust'        => 'RGPD &middot; DIN 66399 &middot; VOG &middot; Assuré &middot; couverture de &euro;&nbsp;2,5 M',

    'ok_title'          => 'Devis accepté',
    'ok_h1'             => 'Merci, votre commande est enregistrée.',
    'ok_p'              => 'Votre devis pour :number a été accepté.',
    'ok_mail'           => 'Une confirmation de commande a été envoyée par e-mail à :email.',
    'ok_amount'         => 'Montant TTC',
    'ok_accepted_at'    => 'Accepté le',
    'ok_next'           => 'Nous vous contactons sous un jour ouvré pour planifier l\'exécution.',

    'sub_title'         => 'Abonnement confirmé',
    'sub_h1'            => 'Merci, votre abonnement est actif.',
    'sub_p'             => 'Votre abonnement :number est confirmé.',
    'sub_mail'          => 'Une confirmation a été envoyée par e-mail à :email.',
    'sub_freq'          => 'Fréquence',
    'sub_term'          => 'Durée',
    'sub_price'         => 'Prix TTC',
    'sub_per_year'      => 'par an',
    'sub_per_4w'        => 'par 4 semaines',
    'sub_confirmed_at'  => 'Confirmé le',
    'sub_next'          => 'Nous vous contactons sous un jour ouvré pour placer le conteneur et convenir du premier enlèvement.',

    'done_title'   => 'Devis déjà accepté',
    'done_banner'  => 'Ce devis a déjà été accepté le :date.',
    'done_h1'      => 'Votre commande est déjà enregistrée.',
    'done_p'       => 'La commande :number est en cours de traitement. Vous avez reçu la confirmation par e-mail à :email.',
    'done_small'   => 'Pas d\'e-mail? Vérifiez vos indésirables ou contactez-nous au :phone.',

    'cancel_title'  => 'Devis annulé',
    'cancel_banner' => 'Ce devis a été annulé.',
    'cancel_banner_date' => 'Ce devis a été annulé le :date.',
    'cancel_h1'     => 'Devis non valable.',
    'cancel_p'      => 'Le devis :number a été retiré et ne peut plus être accepté.',
    'cancel_reason' => 'La raison notée est ":reason".',
    'cancel_new'    => 'Vous souhaitez tout de même donner suite? Contactez-nous au :phone ou à :email pour un nouveau devis.',

    'exp_title'   => 'Devis expiré',
    'exp_banner'  => 'Ce devis a expiré le :date.',
    'exp_h1'      => 'Devis non valable.',
    'exp_p'       => 'La validité du devis :number est dépassée.',
    'exp_new'     => 'Contactez-nous au :phone ou à :email pour un nouveau devis.',

    'validation' => [
        'name'       => 'Indiquez votre nom.',
        'email'      => 'Indiquez votre adresse e-mail.',
        'email_bad'  => 'Indiquez une adresse e-mail valide.',
        'phone'      => 'Indiquez votre numéro de téléphone.',
        'street'     => 'Indiquez le nom de la rue.',
        'number'     => 'Indiquez le numéro.',
        'postcode'   => 'Indiquez votre code postal.',
        'postcode_bad' => 'Indiquez un code postal néerlandais valide, par exemple 1034AB.',
        'city'       => 'Indiquez la ville.',
    ],

];
