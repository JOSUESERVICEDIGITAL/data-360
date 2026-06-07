<?php

namespace App\Http\Requests\Back\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;

class BroadcastNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'title'   => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:1000'],
            'target'  => ['required', 'in:all,premium,free,admins'],
            'type'    => ['required', 'in:info,success,warning,danger'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'   => 'Le titre est obligatoire.',
            'message.required' => 'Le message est obligatoire.',
            'target.required'  => 'Choisissez une cible.',
            'type.required'    => 'Choisissez un type de notification.',
        ];
    }
}