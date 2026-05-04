<?php

namespace App\Models\Back;

use Illuminate\Database\Eloquent\Model;

class Adresse extends Model
{
    protected $fillable = [
        'adresse_complete',
        'numero',
        'voie',
        'code_postal',
        'ville',
        'code_insee',
        'latitude',
        'longitude',
        'source',
        'raw_data',
    ];

    protected $casts = [
        'raw_data' => 'array',
    ];

    // Relations
    public function batiments()
    {
        return $this->hasMany(Batiment::class);
    }

    public function coproprietes()
    {
        return $this->hasMany(Copropriete::class);
    }

    public function recherches()
    {
        return $this->hasMany(Recherche::class);
    }
}