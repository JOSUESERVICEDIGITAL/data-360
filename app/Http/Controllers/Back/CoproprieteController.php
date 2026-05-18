<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Http\Requests\Back\CoproprieteRequest;
use App\Models\Back\Adresse;
use App\Models\Back\Batiment;
use App\Models\Back\Copropriete;
use App\Models\Back\Syndic;

class CoproprieteController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LISTE
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        $coproprietes = Copropriete::with('adresse', 'batiment', 'syndics')
            ->latest()
            ->paginate(15);

        return view('back.coproprietes.index', compact('coproprietes'));
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    */
    public function create()
    {
        return view('back.coproprietes.create', [
            'adresses' => Adresse::orderBy('adresse_complete')->get(),
            'batiments' => Batiment::with('adresse')->latest()->get(),
            'syndics' => Syndic::orderBy('nom')->get(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */
    public function store(CoproprieteRequest $request)
    {
        $data = $request->validated();

        // 🔥 gestion représentant automatique
        if (empty($data['representant_legal_connu'])) {
            $data['representant_legal_connu'] = false;
            $data['message_representant'] = 'Pas de représentant légal connu';
        }

        $copropriete = Copropriete::create($data);

        // 🔥 relation syndics
        if (!empty($data['syndic_ids'])) {
            $copropriete->syndics()->sync($data['syndic_ids']);
        }

        return redirect()
            ->route('back.coproprietes.show', $copropriete)
            ->with('success', 'Copropriété créée avec succès.');
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    */
    public function show(Copropriete $copropriete)
    {
        $copropriete->load('adresse', 'batiment', 'syndics');

        return view('back.coproprietes.show', compact('copropriete'));
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT
    |--------------------------------------------------------------------------
    */
    public function edit(Copropriete $copropriete)
    {
        $copropriete->load('syndics');

        return view('back.coproprietes.edit', [
            'copropriete' => $copropriete,
            'adresses' => Adresse::orderBy('adresse_complete')->get(),
            'batiments' => Batiment::with('adresse')->latest()->get(),
            'syndics' => Syndic::orderBy('nom')->get(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */
    public function update(CoproprieteRequest $request, Copropriete $copropriete)
    {
        $data = $request->validated();

        // 🔥 logique représentant
        if (empty($data['representant_legal_connu'])) {
            $data['representant_legal_connu'] = false;
            $data['message_representant'] = 'Pas de représentant légal connu';
        }

        $copropriete->update($data);

        // 🔥 sync syndics
        if (isset($data['syndic_ids'])) {
            $copropriete->syndics()->sync($data['syndic_ids']);
        }

        return redirect()
            ->route('back.coproprietes.show', $copropriete)
            ->with('success', 'Copropriété mise à jour avec succès.');
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */
    public function destroy(\App\Models\Back\Copropriete $copropriete)
    {
        $copropriete->syndics()->detach();

        $copropriete->delete();

        return redirect()
            ->route('back.coproprietes.index')
            ->with('success', 'Copropriété supprimée avec succès.');
    }

    public function reset()
    {
        \DB::table('copropriete_syndic')->delete();

        \App\Models\Back\Copropriete::query()->delete();

        return redirect()
            ->route('back.coproprietes.index')
            ->with('success', 'Toutes les copropriétés ont été supprimées.');
    }
}
