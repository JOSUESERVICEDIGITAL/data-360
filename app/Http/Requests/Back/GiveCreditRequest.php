<?php

namespace App\Http\Requests\Back;

use Illuminate\Foundation\Http\FormRequest;

class GiveCreditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (bool) auth()->user()->is_admin;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'amount' => ['required', 'integer', 'min:1', 'max:100000'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'Veuillez choisir un utilisateur.',
            'user_id.exists' => 'Utilisateur introuvable.',
            'amount.required' => 'Veuillez saisir un nombre de crédits.',
            'amount.min' => 'Le nombre de crédits doit être supérieur à 0.',
            'amount.max' => 'Le nombre de crédits est trop élevé.',
        ];
    }
}