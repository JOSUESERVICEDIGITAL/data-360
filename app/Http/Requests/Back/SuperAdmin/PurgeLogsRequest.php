<?php

namespace App\Http\Requests\Back\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;

class PurgeLogsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'confirm' => ['required', 'in:CONFIRMER'],
        ];
    }

    public function messages(): array
    {
        return [
            'confirm.required' => 'Vous devez taper CONFIRMER pour valider.',
            'confirm.in' => 'Confirmation invalide. Tapez exactement CONFIRMER.',
        ];
    }
}
