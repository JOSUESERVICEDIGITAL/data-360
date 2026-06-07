<?php

namespace App\Http\Requests\Back\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;

class BulkCreditsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'target'   => ['required', 'in:all,free,premium,enterprise,specific'],
            'action'   => ['required', 'in:add,set,reset'],
            'amount'   => ['required_unless:action,reset', 'integer', 'min:0'],
            'user_ids' => ['required_if:target,specific', 'array'],
            'user_ids.*'=> ['integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'target.required' => 'Veuillez choisir une cible.',
            'action.required' => 'Veuillez choisir une action.',
            'amount.required_unless' => 'Le montant est requis.',
            'amount.min' => 'Le montant doit être positif.',
        ];
    }
}