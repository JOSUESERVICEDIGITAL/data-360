<?php

namespace App\Http\Requests\Back\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;

class PurgeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'confirm' => ['required', 'in:CONFIRMER'],
            'period'  => ['sometimes', 'in:7days,30days,90days,all'],
            'mode'    => ['sometimes', 'in:terminated,older30,all,delete_all'],
        ];
    }

    public function messages(): array
    {
        return [
            'confirm.required' => 'Vous devez taper CONFIRMER pour valider.',
            'confirm.in'       => 'Confirmation invalide. Tapez exactement CONFIRMER.',
        ];
    }
}