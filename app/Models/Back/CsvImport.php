<?php

namespace App\Models\Back;

use Illuminate\Database\Eloquent\Model;

class CsvImport extends Model
{
    protected $fillable = [
        'statut',
        'total_lignes',
        'lignes_traitees',
        'erreur_message',
        'user_id',
        'filename_original',
        'csv_content',
        'filename_result',
        'xlsx_content',
        'statut',
        'total_lignes',
        'lignes_traitees',
        'erreur_message',

    ];

    public function getProgressAttribute(): int
    {
        if (!$this->total_lignes) return 0;
        return (int) round(($this->lignes_traitees / $this->total_lignes) * 100);
    }
}
