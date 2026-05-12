<?php
// app/Http/Requests/Back/NotificationRequest.php

namespace App\Http\Requests\Back;

use Illuminate\Foundation\Http\FormRequest;

class NotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->is_admin ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'exists:users,id'],
            'type' => ['required', 'string', 'in:admin,info,warning,success,danger'],
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:1000'],
            'icon' => ['nullable', 'string', 'max:100'],
            'link' => ['nullable', 'string', 'url', 'max:500'],
            'is_global' => ['boolean'],
            'expires_at' => ['nullable', 'date'],
        ];
    }

    public function attributes(): array
    {
        return [
            'user_id' => 'utilisateur destinataire',
            'type' => 'type de notification',
            'title' => 'titre',
            'message' => 'message',
            'is_global' => 'notification globale',
        ];
    }
}