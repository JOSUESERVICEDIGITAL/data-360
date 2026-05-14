<?php

namespace App\Models\Back;

use Illuminate\Database\Eloquent\Model;

class Copropriete extends Model
{
    protected $fillable = [
        'adresse_id',
        'batiment_id',
        'numero_immatriculation',
        'nom_copropriete',
        'siren_copropriete',
        'nombre_lots_total',
        'nombre_lots_habitation',
        'nombre_batiments',
        'statut',
        'date_immatriculation',
        'raw_data',
    ];

    protected $casts = [
        'raw_data' => 'array',
    ];

    public function adresse()
    {
        return $this->belongsTo(Adresse::class);
    }

    public function batiment()
    {
        return $this->belongsTo(Batiment::class);
    }

    public function syndics()
    {
        return $this->belongsToMany(Syndic::class)
            ->withPivot(['role', 'date_debut', 'date_fin'])
            ->withTimestamps();
    }
}