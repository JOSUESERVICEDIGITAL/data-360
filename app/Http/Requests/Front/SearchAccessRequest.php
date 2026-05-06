<?php

namespace App\Http\Requests\Front;

use Illuminate\Foundation\Http\FormRequest;

class SearchAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:3', 'max:255'],
            'fingerprint' => ['nullable', 'string', 'max:255'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'language' => ['nullable', 'string', 'max:30'],
            'screen' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'q.required' => 'Veuillez saisir une adresse.',
            'q.min' => 'Veuillez saisir une adresse plus complète.',
            'q.max' => 'Votre recherche est trop longue.',
        ];
    }

    public function queryText(): string
    {
        return trim((string) $this->input('q'));
    }
}