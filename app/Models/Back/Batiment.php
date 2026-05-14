<?php

namespace App\Models\Back;

use Illuminate\Database\Eloquent\Model;

class Batiment extends Model
{
    protected $fillable = [
        'adresse_id',
        'identifiant_bdnb',
        'identifiant_cadastre',
        'type_batiment',
        'annee_construction',
        'nombre_logements',
        'nombre_niveaux',
        'hauteur',
        'surface_habitable',
        'surface_emprise_sol',
        'classe_dpe',
        'ges',
        'type_chauffage',
        'energie_chauffage',
        'score_opportunite',
        'raw_data',
    ];

    protected $casts = [
        'raw_data' => 'array',
    ];

    public function adresse()
    {
        return $this->belongsTo(Adresse::class);
    }

    public function coproprietes()
    {
        return $this->hasMany(Copropriete::class);
    }
}