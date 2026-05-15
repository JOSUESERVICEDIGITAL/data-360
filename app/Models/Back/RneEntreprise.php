<?php

namespace App\Models\Back;

use Illuminate\Database\Eloquent\Model;

class RneEntreprise extends Model
{
    protected $table = 'rne_entreprises';

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

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getCapitalFormattedAttribute(): ?string
    {
        if (!$this->capital_social_numeric) {
            return $this->capital_social;
        }

        return number_format(
            (float) $this->capital_social_numeric,
            0,
            ',',
            ' '
        ) . ' €';
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeSearch($query, ?string $value)
    {
        if (!$value) {
            return $query;
        }

        return $query->where(function ($q) use ($value) {
            $q->where('denomination', 'like', "%{$value}%")
                ->orWhere('siren', 'like', "%{$value}%")
                ->orWhere('siret_siege', 'like', "%{$value}%")
                ->orWhere('ville', 'like', "%{$value}%")
                ->orWhere('code_postal', 'like', "%{$value}%");
        });
    }

    public function scopeWithCapital($query)
    {
        return $query->whereNotNull('capital_social_numeric');
    }
}