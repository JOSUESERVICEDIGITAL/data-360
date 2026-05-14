<?php

namespace App\Http\Requests\Back;

use Illuminate\Foundation\Http\FormRequest;

class CoproprieteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'adresse_id' => ['nullable', 'exists:adresses,id'],
            'batiment_id' => ['nullable', 'exists:batiments,id'],

            'numero_immatriculation' => ['nullable', 'string', 'max:100'],
            'nom_copropriete' => ['nullable', 'string', 'max:255'],
            'siren_copropriete' => ['nullable', 'digits:9'],

            'nombre_lots_total' => ['nullable', 'integer', 'min:0'],
            'nombre_lots_habitation' => ['nullable', 'integer', 'min:0'],
            'nombre_batiments' => ['nullable', 'integer', 'min:0'],
            'nombre_adresses_associees' => ['nullable', 'integer', 'min:0'],

            'statut' => ['nullable', 'string', 'max:100'],
            'date_immatriculation' => ['nullable', 'date'],

            // 🔥 représentant légal
            'representant_legal_nom' => ['nullable', 'string', 'max:255'],
            'representant_legal_type' => ['nullable', 'string', 'max:100'],
            'representant_legal_connu' => ['nullable', 'boolean'],
            'message_representant' => ['nullable', 'string', 'max:255'],

            'raw_data' => ['nullable', 'array'],

            // 🔥 relation many-to-many
            'syndic_ids' => ['nullable', 'array'],
            'syndic_ids.*' => ['exists:syndics,id'],
        ];
    }
}