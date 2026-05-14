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

        'capital_social',
        'chiffre_affaires',
        'resultat',
        'effectif',
        'date_creation',
        'dirigeant_principal',
        'url_pappers',

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

    public function getIdentiteCompleteAttribute(): string
    {
        return trim(($this->nom ?? '-') . ' — SIREN : ' . ($this->siren ?? '-'));
    }

    public function getCapitalLabelAttribute(): string
    {
        return $this->capital_social ?: 'Non renseigné';
    }

    public function getPappersLinkAttribute(): ?string
    {
        if ($this->url_pappers) {
            return $this->url_pappers;
        }

        return $this->siren
            ? 'https://www.pappers.fr/entreprise/' . $this->siren
            : null;
    }
}