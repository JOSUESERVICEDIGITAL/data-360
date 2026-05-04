<?php

namespace App\Http\Requests\Back;

use Illuminate\Foundation\Http\FormRequest;

class AdresseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'adresse_complete' => ['required', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:20'],
            'voie' => ['nullable', 'string', 'max:255'],
            'code_postal' => ['nullable', 'string', 'max:10'],
            'ville' => ['nullable', 'string', 'max:100'],
            'code_insee' => ['nullable', 'string', 'max:10'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'source' => ['nullable', 'string', 'max:100'],
            'raw_data' => ['nullable', 'array'],
        ];
    }
}