<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Honeypot gevuld: bot. Niets valideren, de controller geeft een nette
        // 201 terug zonder op te slaan. Zelfde truc als OfferteRequest.
        if (filled($this->input('website'))) {
            return [];
        }

        return [
            'naam'      => 'required|string|max:255',
            'bedrijf'   => 'nullable|string|max:255',
            'email'     => 'required|email|max:255',
            'telefoon'  => 'nullable|string|max:50',
            'adres'     => 'nullable|string|max:255',
            'postcode'  => 'required|string|max:20',
            'plaats'    => 'nullable|string|max:100',
            'term'      => ['required', Rule::in(array_keys(Order::SUB_TERMS))],
            'freq'      => ['required', Rule::in(array_keys(Order::SUB_FREQS))],
            'opmerking' => 'nullable|string|max:5000',
            'akkoord'   => 'required|accepted',
            // Door de postcodecheck op het formulier: buiten het werkgebied is
            // dit een wachtlijstregel, geen boekbaar abonnement.
            'waitlist'   => 'nullable|boolean',
            'afstand_km' => 'nullable|integer|min:0|max:1000',
            'website'   => 'nullable|string|max:255',
            'lang'      => 'nullable|in:nl,en,fr,es',
        ];
    }

    public function messages(): array
    {
        return [
            'naam.required'     => 'Vul uw naam in.',
            'email.required'    => 'Vul uw e-mailadres in.',
            'email.email'       => 'Vul een geldig e-mailadres in.',
            'postcode.required' => 'Vul uw postcode in.',
            'term.required'     => 'Kies een looptijd.',
            'term.in'           => 'Kies een geldige looptijd.',
            'freq.required'     => 'Kies een frequentie.',
            'freq.in'           => 'Kies een geldige frequentie.',
            'akkoord.accepted'  => 'Ga akkoord met de verwerking van uw gegevens.',
        ];
    }
}
