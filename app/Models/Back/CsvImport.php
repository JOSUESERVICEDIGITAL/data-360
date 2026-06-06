<?php

namespace App\Models\Back;

use Illuminate\Database\Eloquent\Model;

class CsvImport extends Model
{
    protected $fillable = [
        'user_id', 'filename_original', 'filename_result',
        'statut', 'total_lignes', 'lignes_traitees', 'erreur_message',
    ];

    public function getProgressAttribute(): int
    {
        if (!$this->total_lignes) return 0;
        return (int) round(($this->lignes_traitees / $this->total_lignes) * 100);
    }
}