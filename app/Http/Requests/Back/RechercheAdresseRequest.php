<?php

namespace App\Http\Requests\Back;

use Illuminate\Foundation\Http\FormRequest;

class RechercheAdresseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'requete' => ['required', 'string', 'min:5', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'requete.required' => 'Veuillez saisir une adresse',
            'requete.min' => 'Adresse trop courte',
        ];
    }
}