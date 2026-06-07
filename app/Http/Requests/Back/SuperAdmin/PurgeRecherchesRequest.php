<?php

namespace App\Http\Requests\Back\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;

class PurgeRecherchesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'period'  => ['required', 'in:7days,30days,90days,all'],
            'confirm' => ['required', 'in:CONFIRMER'],
        ];
    }

    public function messages(): array
    {
        return [
            'period.required' => 'Veuillez sélectionner une période.',
            'confirm.required' => 'Vous devez taper CONFIRMER pour valider.',
            'confirm.in' => 'Confirmation invalide. Tapez exactement CONFIRMER.',
        ];
    }
}
