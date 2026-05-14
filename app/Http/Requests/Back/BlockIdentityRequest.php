<?php

namespace App\Http\Requests\Back;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BlockIdentityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (bool) auth()->user()->is_admin;
    }

    public function rules(): array
    {
        return [
            'type' => [
                'required',
                'string',
                Rule::in(['ip', 'fingerprint', 'user', 'email_domain', 'phone', 'device']),
            ],
            'value' => ['required', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Veuillez choisir le type de blocage.',
            'type.in' => 'Type de blocage invalide.',
            'value.required' => 'Veuillez saisir la valeur à bloquer.',
            'expires_at.after' => 'La date d’expiration doit être dans le futur.',
        ];
    }
}