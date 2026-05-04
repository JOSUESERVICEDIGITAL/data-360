<?php

namespace App\Http\Requests\Back;

use Illuminate\Foundation\Http\FormRequest;

class BatimentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'adresse_id' => ['nullable', 'exists:adresses,id'],
            'identifiant_bdnb' => ['nullable', 'string', 'max:100'],
            'identifiant_cadastre' => ['nullable', 'string', 'max:100'],

            'type_batiment' => ['required', 'in:individuel,collectif,tertiaire,mixte,inconnu'],

            'annee_construction' => ['nullable', 'integer', 'between:1800,' . date('Y')],
            'nombre_logements' => ['nullable', 'integer', 'min:0'],
            'nombre_niveaux' => ['nullable', 'integer', 'min:0'],

            'hauteur' => ['nullable', 'numeric'],
            'surface_habitable' => ['nullable', 'numeric'],
            'surface_emprise_sol' => ['nullable', 'numeric'],

            'classe_dpe' => ['nullable', 'string', 'max:5'],
            'ges' => ['nullable', 'string', 'max:5'],

            'type_chauffage' => ['nullable', 'string', 'max:100'],
            'energie_chauffage' => ['nullable', 'string', 'max:100'],

            'score_opportunite' => ['nullable', 'numeric'],
            'raw_data' => ['nullable', 'array'],
        ];
    }
}