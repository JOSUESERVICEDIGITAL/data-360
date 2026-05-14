<?php

namespace App\Models\Back;

use Illuminate\Database\Eloquent\Model;

class Recherche extends Model
{
    protected $fillable = [
        'user_id',
        'adresse_id',
        'requete',
        'statut',
        'message',
        'resultat',
    ];

    protected $casts = [
        'resultat' => 'array',
    ];

    public function adresse()
    {
        return $this->belongsTo(Adresse::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}