<?php

namespace App\Models\Back;

use Illuminate\Database\Eloquent\Model;

class QpvZone extends Model
{
    protected $fillable = [
        'type',
        'code',
        'nom',
        'geojson',
    ];
}