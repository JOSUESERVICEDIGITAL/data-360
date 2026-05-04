<?php

namespace App\Http\Requests\Back;

use Illuminate\Foundation\Http\FormRequest;

class SyndicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => ['nullable', 'string', 'max:255'],

            'siren' => ['nullable', 'digits:9'],
            'siret' => ['nullable', 'digits:14'],

            'forme_juridique' => ['nullable', 'string', 'max:100'],
            'activite' => ['nullable', 'string', 'max:255'],

            'adresse_complete' => ['nullable', 'string', 'max:255'],
            'code_postal' => ['nullable', 'string', 'max:10'],
            'ville' => ['nullable', 'string', 'max:100'],

            'telephone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email'],

            'raw_data' => ['nullable', 'array'],
        ];
    }
}