<?php

namespace App\Models\Back;

use Illuminate\Database\Eloquent\Model;

class ImportCsv extends Model
{
    protected $fillable = [
        'nom_fichier',
        'chemin',
        'total_lignes',
        'lignes_traitees',
        'statut',
    ];
}