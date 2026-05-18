<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Http\Requests\Back\RechercheAdresseRequest;
use App\Models\Back\Adresse;
use App\Models\Back\Batiment;
use App\Models\Back\Copropriete;
use App\Models\Back\Recherche;
use Illuminate\Support\Facades\Auth;

class RechercheController extends Controller
{
    public function index()
    {
        $recherches = Recherche::with('adresse', 'user')
            ->latest()
            ->paginate(15);

        return view('back.recherches.index', compact('recherches'));
    }

    public function create()
    {
        return view('back.recherches.create');
    }

    public function search(RechercheAdresseRequest $request)
    {
        $requete = $request->validated()['requete'];

        $adresse = Adresse::firstOrCreate(
            ['adresse_complete' => $requete],
            [
                'source' => 'manuel',
                'raw_data' => [
                    'requete_initiale' => $requete,
                ],
            ]
        );

        $batiments = Batiment::with('coproprietes.syndics')
            ->where('adresse_id', $adresse->id)
            ->get();

        $coproprietes = Copropriete::with('syndics')
            ->where('adresse_id', $adresse->id)
            ->orWhereIn('batiment_id', $batiments->pluck('id'))
            ->get();

        $resultat = [
            'adresse' => $adresse,
            'batiments' => $batiments,
            'coproprietes' => $coproprietes,
        ];

        $statut = $batiments->isNotEmpty() || $coproprietes->isNotEmpty()
            ? 'trouve'
            : 'partiel';

        $recherche = Recherche::create([
            'user_id' => Auth::id(),
            'adresse_id' => $adresse->id,
            'requete' => $requete,
            'statut' => $statut,
            'message' => $statut === 'trouve'
                ? 'Résultat trouvé dans la base locale.'
                : 'Adresse enregistrée, enrichissement API à faire.',
            'resultat' => $resultat,
        ]);

        return redirect()
            ->route('back.recherches.show', $recherche)
            ->with('success', 'Recherche effectuée.');
    }

    public function show(Recherche $recherche)
    {
        $recherche->load('adresse.batiments.coproprietes.syndics', 'adresse.coproprietes.syndics');

        return view('back.recherches.show', compact('recherche'));
    }

    public function destroy(Recherche $recherche)
    {
        $recherche->delete();

        return redirect()
            ->route('back.recherches.index')
            ->with('success', 'Recherche supprimée avec succès.');
    }

    public function reset()
    {
        Recherche::query()->delete();

        return redirect()
            ->route('back.recherches.index')
            ->with('success', 'Toutes les recherches ont été supprimées.');
    }
}
