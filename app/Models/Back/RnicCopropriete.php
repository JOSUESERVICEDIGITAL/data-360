<?php

namespace App\Models\Back;

use Illuminate\Database\Eloquent\Model;

class RnicCopropriete extends Model
{
    protected $table = 'rnic_coproprietes';

    protected $fillable = [
        'numero_immatriculation',
        'nom_copropriete',
        'adresse_complete',
        'code_postal',
        'ville',
        'code_insee',
        'siren_copropriete',
        'nombre_lots_total',
        'nombre_lots_habitation',
        'nombre_batiments',
        'nombre_adresses_associees',
        'representant_legal_connu',
        'representant_legal_nom',
        'representant_legal_type',
        'message_representant',
        'syndic_nom',
        'siren_syndic',
        'siret_syndic',
        'statut',
        'date_immatriculation',
        'raw_data',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'representant_legal_connu' => 'boolean',
        'date_immatriculation' => 'date',
    ];
}