<?php

namespace App\Models\Back;

use Illuminate\Database\Eloquent\Model;

class Adresse extends Model
{
    protected $table = 'adresses';

    protected $fillable = [
        'adresse_complete',
        'adresse_hash',      // ← nouveau
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
        'latitude'  => 'float',
        'longitude' => 'float',
        'raw_data'  => 'array',
    ];

    // ─── Relations ────────────────────────────────────────────
    public function batiments()
    {
        return $this->hasMany(Batiment::class);
    }

    public function coproprietes()
    {
        return $this->hasMany(Copropriete::class);
    }

    // ─── Génération du hash avant save ────────────────────────
    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if (!empty($model->adresse_complete)) {
                $model->adresse_hash = self::makeHash($model->adresse_complete);
            }
        });
    }

    /**
     * Hash déterministe pour une adresse
     * md5(strtolower(trim())) → char(32) indexable
     */
    public static function makeHash(string $adresse): string
    {
        return md5(strtolower(trim($adresse)));
    }

    /**
     * Recherche ultra-rapide par hash — O(1) sur index unique
     * Remplace : WHERE adresse_complete LIKE '%...%' (scan table)
     * Par       : WHERE adresse_hash = 'abc123...'  (index lookup)
     */
    public static function findByAdresse(string $adresse): ?self
    {
        return static::where('adresse_hash', self::makeHash($adresse))->first();
    }

    /**
     * Recherche ou création — utilise le hash pour la clé unique
     */
    public static function findOrCreateByAdresse(string $adresse, array $data = []): self
    {
        $hash = self::makeHash($adresse);

        return static::firstOrCreate(
            ['adresse_hash'     => $hash],
            array_merge($data, ['adresse_complete' => $adresse])
        );
    }
}