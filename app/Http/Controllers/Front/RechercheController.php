<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Services\Api\DataRocketEngineService;
use Illuminate\Http\Request;

class RechercheController extends Controller
{
    public function index(Request $request, DataRocketEngineService $engine)
    {
        $q = $request->query('q');

        if (!$q) {
            return redirect()->route('front.home');
        }

        $resultat = $engine->searchByAddress($q);

        return view('front.recherche.result', [
            'q' => $q,
            'resultat' => $resultat,
            'adresse' => $resultat['adresse'] ?? null,
        ]);
    }
}