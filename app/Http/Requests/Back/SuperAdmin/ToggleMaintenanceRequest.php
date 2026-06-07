<?php

namespace App\Http\Requests\Back\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;

class ToggleMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'action'  => ['required', 'in:up,down'],
            'secret'  => ['nullable', 'string', 'max:64'],
            'message' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'L\'action est obligatoire.',
            'action.in'       => 'Action invalide — choisissez up ou down.',
        ];
    }
}