<?php

namespace App\Models\Back;

use Illuminate\Database\Eloquent\Model;

class Syndic extends Model
{
    protected $fillable = [
        'nom',
        'siren',
        'siret',
        'forme_juridique',
        'activite',
        'adresse_complete',
        'code_postal',
        'ville',
        'telephone',
        'email',
        'raw_data',
    ];

    protected $casts = [
        'raw_data' => 'array',
    ];

    public function coproprietes()
    {
        return $this->belongsToMany(Copropriete::class)
            ->withPivot(['role', 'date_debut', 'date_fin'])
            ->withTimestamps();
    }
}