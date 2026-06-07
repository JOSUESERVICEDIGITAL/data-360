<?php

namespace App\Http\Requests\Back\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;

class PurgeImportsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'mode'    => ['required', 'in:terminated,older30,all,delete_all'],
            'confirm' => ['required', 'in:CONFIRMER'],
        ];
    }

    public function messages(): array
    {
        return [
            'mode.required' => 'Veuillez sélectionner un mode de purge.',
            'confirm.required' => 'Vous devez taper CONFIRMER pour valider.',
            'confirm.in' => 'Confirmation invalide. Tapez exactement CONFIRMER.',
        ];
    }
}
