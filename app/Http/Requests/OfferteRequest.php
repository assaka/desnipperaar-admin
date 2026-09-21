<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OfferteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Het offerteformulier vroeg vroeger om "Plaats / postcode" en stuurde dat
     * als 'plaats'. Het veld heet nu postcode. Hier alleen normaliseren naar de
     * oude sleutel, zodat pagina's uit een browsercache blijven werken en de
     * controller ongewijzigd kan blijven.
     */
    protected function prepareForValidation(): void
    {
        if (filled($this->input('postcode')) && blank($this->input('plaats'))) {
            $this->merge(['plaats' => $this->input('postcode')]);
        }
    }

    public function rules(): array
    {
        // Honeypot filled → skip validation so bots see the same 201 a legit submit would get.
        // The controller handles the honeypot response before inspecting validated data.
        if (filled($this->input('website'))) {
            return [];
        }

        return [
            'naam'       => 'required|string|max:255',
            'bedrijf'    => 'nullable|string|max:255',
            'email'      => 'required|email|max:255',
            'telefoon'   => 'required|string|max:50',

            // De postcode is verplicht: zonder die ene regel weten we niet of de
            // rit binnen de gratis straal valt, dus kunnen we geen prijs noemen.
            // De regel heet intern nog 'plaats', net als op /api/order. De rest
            // van het adres mag leeg blijven, want een prospect weet soms nog
            // geen huisnummer.
            'plaats'     => 'required|string|max:10|regex:/^\d{4}\s?[A-Za-z]{2}$/',
            'adres'      => 'nullable|string|max:255',
            'straat'     => 'nullable|string|max:255',
            'huisnummer' => 'nullable|string|max:20',
            'stad'       => 'nullable|string|max:100',

            // Afwijkend ophaaladres, als één regel vrije tekst. Het
            // offerteformulier kent geen postcodecontrole en de aanvrager weet
            // soms nog niet meer dan de plaats, dus hier geen losse velden.
            'ophaal_adres' => 'nullable|string|max:255',

            'branche'    => 'nullable|string|max:100',
            // Attributievraag van het formulier. Alleen ter informatie in de
            // notities, dus geen eigen kolom en geen vaste lijst.
            'gevonden_via' => 'nullable|string|max:100',
            'type'       => 'required|string|max:200',
            'volume'     => 'nullable|string|max:500',
            'methode'    => 'nullable|string|max:50',
            'termijn'    => 'nullable|string|max:100',
            'bericht'    => 'nullable|string|max:5000',

            // Vertrouwelijk transport van A naar B (methode = transport). Alleen
            // gevuld als die tegel gekozen is, dus allemaal nullable: een offerte
            // voor vernietiging stuurt ze niet mee. Zonder deze regels laat
            // validated() ze stilletjes vallen.
            'transport_van'             => 'nullable|string|max:255',
            'transport_naar'            => 'nullable|string|max:255',
            'transport_ontvanger'       => 'nullable|string|max:255',
            'transport_ontvanger_email' => 'nullable|email|max:255',
            'transport_colli'           => 'nullable|integer|min:1|max:9999',
            'transport_datum'           => 'nullable|date',
            'transport_slot'            => 'nullable|in:standaard,tracking',

            'akkoord'    => 'required|accepted',

            'website'    => 'nullable|string|max:255',
            'lang'       => 'nullable|in:nl,en,fr,es',
        ];
    }

    public function messages(): array
    {
        return [
            'naam.required'     => 'Vul uw naam in.',
            'email.required'    => 'Vul uw e-mailadres in.',
            'email.email'       => 'Vul een geldig e-mailadres in.',
            'telefoon.required' => 'Vul uw telefoonnummer in.',
            'plaats.required'   => 'Vul uw postcode in.',
            'plaats.regex'      => 'Vul een geldige postcode in, bijvoorbeeld 1034 AB.',
            'type.required'     => 'Kies het type materiaal.',
            'akkoord.required'  => 'Ga akkoord met verwerking van uw gegevens.',
            'akkoord.accepted'  => 'Ga akkoord met verwerking van uw gegevens.',
        ];
    }

    public function attributes(): array
    {
        // De regel heet intern 'plaats', het formulierveld heet postcode. Zonder
        // deze vertaling leest de bezoeker een foutmelding over een veld dat hij
        // niet ziet.
        return [
            'plaats' => 'postcode',
        ];
    }
}
