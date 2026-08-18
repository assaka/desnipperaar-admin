<?php

// Textos en español de la página pública de presupuesto. Véase lang/nl/quote.php
// para saber por qué los importes y las fechas mantienen el formato neerlandés.

return [

    'title'        => 'Presupuesto :number',
    'eyebrow'      => 'Presupuesto',
    'h1'           => 'Su presupuesto a medida',

    'banner_accepted' => 'Este presupuesto ya se aceptó el :date. La confirmación del pedido está en camino.',
    'banner_expired'  => 'Este presupuesto venció el :date. Póngase en contacto para uno nuevo.',

    'scope_h'      => 'Alcance y precio',
    'th_desc'      => 'Descripción',
    'th_qty'       => 'Cantidad',
    'th_price'     => 'Precio',
    'th_subtotal'  => 'Subtotal',

    'options_h'    => 'Opciones adicionales',
    'options_help' => 'Marque lo que desee añadir. El total se actualiza al instante.',

    'amount_excl'  => 'Importe sin IVA',
    'vat'          => 'IVA 21%',
    'incl_vat'     => 'IVA incluido',
    'valid_until'  => 'Este presupuesto es válido hasta el :date.',

    'details_h'    => 'Sus datos',
    'details_help' => 'Indique la dirección donde realizamos el trabajo. Después realiza el pedido.',

    'f_name'       => 'Nombre',
    'f_email'      => 'Correo electrónico',
    'f_company'    => 'Empresa',
    'f_optional'   => '(opcional)',
    'f_phone'      => 'Teléfono',
    'f_street'     => 'Calle',
    'f_number'     => 'Número',
    'f_postcode'   => 'Código postal',
    'f_city'       => 'Ciudad',

    'submit'       => 'Realizar pedido',
    'legal'        => 'Al hacer clic en <strong>:button</strong> acepta el importe de <strong>:amount</strong> con IVA y nuestras <a href=":terms" target="_blank" style="color:#0A0A0A;">condiciones generales</a>. Su dirección IP y la hora se registran como prueba.',

    'modal_h'      => '¿Realizar el pedido?',
    'modal_p'      => 'Va a realizar un pedido basado en el presupuesto <strong style="font-family:monospace;">:number</strong>. Es un pedido vinculante por <strong>:amount</strong> con IVA.',
    'modal_cancel' => 'Cancelar',
    'modal_ok'     => 'Sí, realizar pedido',

    'trust'        => 'RGPD &middot; DIN 66399 &middot; VOG &middot; Asegurado &middot; cobertura de &euro;&nbsp;2,5 M',

    'ok_title'          => 'Presupuesto aceptado',
    'ok_h1'             => 'Gracias, su pedido está realizado.',
    'ok_p'              => 'Su presupuesto de :number ha sido aceptado.',
    'ok_mail'           => 'Se ha enviado una confirmación de pedido por correo a :email.',
    'ok_amount'         => 'Importe con IVA',
    'ok_accepted_at'    => 'Aceptado el',
    'ok_next'           => 'Nos pondremos en contacto en un día laborable para programar la ejecución.',

    'sub_title'         => 'Suscripción confirmada',
    'sub_h1'            => 'Gracias, su suscripción está activa.',
    'sub_p'             => 'Su suscripción :number está confirmada.',
    'sub_mail'          => 'Se ha enviado una confirmación por correo a :email.',
    'sub_freq'          => 'Frecuencia',
    'sub_term'          => 'Duración',
    'sub_price'         => 'Precio con IVA',
    'sub_per_year'      => 'al año',
    'sub_per_4w'        => 'cada 4 semanas',
    'sub_confirmed_at'  => 'Confirmado el',
    'sub_next'          => 'Nos pondremos en contacto en un día laborable para colocar el contenedor y acordar la primera recogida.',

    'done_title'   => 'Presupuesto ya aceptado',
    'done_banner'  => 'Este presupuesto ya se aceptó el :date.',
    'done_h1'      => 'Su pedido ya está realizado.',
    'done_p'       => 'El pedido :number está en curso. Ha recibido la confirmación por correo en :email.',
    'done_small'   => '¿No ha recibido el correo? Revise el spam o contacte con nosotros en el :phone.',

    'cancel_title'  => 'Presupuesto cancelado',
    'cancel_banner' => 'Este presupuesto ha sido cancelado.',
    'cancel_banner_date' => 'Este presupuesto se canceló el :date.',
    'cancel_h1'     => 'Presupuesto no válido.',
    'cancel_p'      => 'El presupuesto :number ha sido retirado y ya no se puede aceptar.',
    'cancel_reason' => 'El motivo que anotamos es ":reason".',
    'cancel_new'    => '¿Sigue adelante igualmente? Contacte con nosotros en el :phone o en :email para un nuevo presupuesto.',

    'exp_title'   => 'Presupuesto vencido',
    'exp_banner'  => 'Este presupuesto venció el :date.',
    'exp_h1'      => 'Presupuesto no válido.',
    'exp_p'       => 'La validez del presupuesto :number ha expirado.',
    'exp_new'     => 'Contacte con nosotros en el :phone o en :email para un nuevo presupuesto.',

    'validation' => [
        'name'       => 'Indique su nombre.',
        'email'      => 'Indique su correo electrónico.',
        'email_bad'  => 'Indique un correo electrónico válido.',
        'phone'      => 'Indique su número de teléfono.',
        'street'     => 'Indique el nombre de la calle.',
        'number'     => 'Indique el número.',
        'postcode'   => 'Indique su código postal.',
        'postcode_bad' => 'Indique un código postal neerlandés válido, por ejemplo 1034AB.',
        'city'       => 'Indique la ciudad.',
    ],

];
