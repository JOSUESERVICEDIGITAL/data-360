<?php

namespace App\Models\Back;

use Illuminate\Database\Eloquent\Model;

class RneEntreprise extends Model
{
    protected $fillable = [
        'siren',
        'siret_siege',
        'denomination',
        'forme_juridique',
        'capital_social',
        'capital_social_numeric',
        'activite',
        'date_creation',
        'adresse_complete',
        'code_postal',
        'ville',
        'dirigeants',
        'etablissements',
        'raw_data',
    ];

    protected $casts = [
        'date_creation' => 'date',
        'capital_social_numeric' => 'decimal:2',
        'dirigeants' => 'array',
        'etablissements' => 'array',
        'raw_data' => 'array',
    ];
}