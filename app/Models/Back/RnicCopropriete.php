<?php

namespace App\Models\Back;

use Illuminate\Database\Eloquent\Model;

class RnicCopropriete extends Model
{
    protected $table = 'rnic_coproprietes';

    protected $guarded = [];

    protected $casts = [
        'raw_data' => 'array',
        'representant_legal_connu' => 'boolean',
        'date_immatriculation' => 'date',
    ];
}