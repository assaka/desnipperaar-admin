<?php

// English strings for the public quote page. See lang/nl/quote.php for why
// amounts and dates keep Dutch formatting in every language.

return [

    'title'        => 'Quote :number',
    'eyebrow'      => 'Quote',
    'h1'           => 'Your tailored quote',

    'banner_accepted' => 'This quote was already accepted on :date. An order confirmation is on its way.',
    'banner_expired'  => 'This quote expired on :date. Get in touch for a new one.',

    'scope_h'      => 'Scope and price',
    'th_desc'      => 'Description',
    'th_qty'       => 'Quantity',
    'th_price'     => 'Price',
    'th_subtotal'  => 'Subtotal',

    'options_h'    => 'Optional extras',
    'options_help' => 'Tick what you want to add. The total updates immediately.',

    'amount_excl'  => 'Amount excl. VAT',
    'vat'          => 'VAT 21%',
    'incl_vat'     => 'incl. VAT',
    'valid_until'  => 'This quote is valid until :date.',

    'details_h'    => 'Your details',
    'details_help' => 'Fill in the address where we carry out the job. Then you place the order.',

    'f_name'       => 'Name',
    'f_email'      => 'Email address',
    'f_company'    => 'Company',
    'f_optional'   => '(optional)',
    'f_phone'      => 'Phone',
    'f_street'     => 'Street',
    'f_number'     => 'House number',
    'f_postcode'   => 'Postcode',
    'f_city'       => 'City',

    'submit'       => 'Place order',
    'legal'        => 'By clicking <strong>:button</strong> you agree to the amount of <strong>:amount</strong> incl. VAT and to our <a href=":terms" target="_blank" style="color:#0A0A0A;">general terms and conditions</a>. Your IP address and the time are recorded as evidence.',

    'modal_h'      => 'Place the order?',
    'modal_p'      => 'You are placing an order based on quote <strong style="font-family:monospace;">:number</strong>. This is a binding order for <strong>:amount</strong> incl. VAT.',
    'modal_cancel' => 'Cancel',
    'modal_ok'     => 'Yes, place the order',

    'trust'        => 'GDPR &middot; DIN 66399 &middot; VOG &middot; Insured &middot; &euro;&nbsp;2.5m cover',

    'ok_title'          => 'Quote accepted',
    'ok_h1'             => 'Thank you, your order is placed.',
    'ok_p'              => 'Your quote for :number has been accepted.',
    'ok_mail'           => 'An order confirmation has been emailed to :email.',
    'ok_amount'         => 'Amount incl. VAT',
    'ok_accepted_at'    => 'Accepted on',
    'ok_next'           => 'We will contact you within one working day to schedule the job.',

    'sub_title'         => 'Subscription confirmed',
    'sub_h1'            => 'Thank you, your subscription is running.',
    'sub_p'             => 'Your subscription :number is confirmed.',
    'sub_mail'          => 'A confirmation has been emailed to :email.',
    'sub_freq'          => 'Frequency',
    'sub_term'          => 'Term',
    'sub_price'         => 'Price incl. VAT',
    'sub_per_year'      => 'per year',
    'sub_per_4w'        => 'per 4 weeks',
    'sub_confirmed_at'  => 'Confirmed on',
    'sub_next'          => 'We will contact you within one working day to place the container and agree the first pickup.',

    'done_title'   => 'Quote already accepted',
    'done_banner'  => 'This quote was already accepted on :date.',
    'done_h1'      => 'Your order is already placed.',
    'done_p'       => 'Order :number is being handled. You received the order confirmation by email at :email.',
    'done_small'   => 'No email? Check your spam folder or get in touch on :phone.',

    'cancel_title'  => 'Quote cancelled',
    'cancel_banner' => 'This quote has been cancelled.',
    'cancel_banner_date' => 'This quote was cancelled on :date.',
    'cancel_h1'     => 'Quote no longer valid.',
    'cancel_p'      => 'Quote :number has been withdrawn and can no longer be accepted.',
    'cancel_reason' => 'The reason we noted is ":reason".',
    'cancel_new'    => 'Going ahead after all? Get in touch on :phone or :email for a new quote.',

    'exp_title'   => 'Quote expired',
    'exp_banner'  => 'This quote expired on :date.',
    'exp_h1'      => 'Quote no longer valid.',
    'exp_p'       => 'Quote :number has passed its validity date.',
    'exp_new'     => 'Get in touch on :phone or :email for a new quote.',

    'validation' => [
        'name'       => 'Please fill in your name.',
        'email'      => 'Please fill in your email address.',
        'email_bad'  => 'Please fill in a valid email address.',
        'phone'      => 'Please fill in your phone number.',
        'street'     => 'Please fill in the street name.',
        'number'     => 'Please fill in the house number.',
        'postcode'   => 'Please fill in your postcode.',
        'postcode_bad' => 'Please fill in a valid Dutch postcode, for example 1034AB.',
        'city'       => 'Please fill in the city.',
    ],

];
