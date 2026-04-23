<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OfferteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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

            // Address is optional on the /offerte form — prospects may not have a
            // delivery address yet. Order form collects these separately.
            'plaats'     => 'nullable|string|max:100',
            'adres'      => 'nullable|string|max:255',
            'straat'     => 'nullable|string|max:255',
            'huisnummer' => 'nullable|string|max:20',
            'stad'       => 'nullable|string|max:100',

            'branche'    => 'nullable|string|max:100',
            'type'       => 'required|string|max:200',
            'volume'     => 'nullable|string|max:500',
            'methode'    => 'nullable|string|max:50',
            'termijn'    => 'nullable|string|max:100',
            'bericht'    => 'nullable|string|max:5000',

            'akkoord'    => 'required|accepted',

            'website'    => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'naam.required'     => 'Vul uw naam in.',
            'email.required'    => 'Vul uw e-mailadres in.',
            'email.email'       => 'Vul een geldig e-mailadres in.',
            'telefoon.required' => 'Vul uw telefoonnummer in.',
            'type.required'     => 'Kies het type materiaal.',
            'akkoord.required'  => 'Ga akkoord met verwerking van uw gegevens.',
            'akkoord.accepted'  => 'Ga akkoord met verwerking van uw gegevens.',
        ];
    }
}
