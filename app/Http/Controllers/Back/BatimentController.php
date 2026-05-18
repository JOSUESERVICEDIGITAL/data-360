<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Http\Requests\Back\BatimentRequest;
use App\Models\Back\Adresse;
use App\Models\Back\Batiment;

class BatimentController extends Controller
{
    public function index()
    {
        $batiments = Batiment::with('adresse')
            ->latest()
            ->paginate(15);

        return view('back.batiments.index', compact('batiments'));
    }

    public function create()
    {
        $adresses = Adresse::orderBy('adresse_complete')->get();

        return view('back.batiments.create', compact('adresses'));
    }

    public function store(BatimentRequest $request)
    {
        Batiment::create($request->validated());

        return redirect()
            ->route('back.batiments.index')
            ->with('success', 'Bâtiment créé avec succès.');
    }

    public function show(Batiment $batiment)
    {
        $batiment->load('adresse', 'coproprietes.syndics');

        return view('back.batiments.show', compact('batiment'));
    }

    public function edit(Batiment $batiment)
    {
        $adresses = Adresse::orderBy('adresse_complete')->get();

        return view('back.batiments.edit', compact('batiment', 'adresses'));
    }

    public function update(BatimentRequest $request, Batiment $batiment)
    {
        $batiment->update($request->validated());

        return redirect()
            ->route('back.batiments.show', $batiment)
            ->with('success', 'Bâtiment mis à jour avec succès.');
    }

    public function destroy(\App\Models\Back\Batiment $batiment)
    {
        $batiment->delete();

        return redirect()
            ->route('back.batiments.index')
            ->with('success', 'Bâtiment supprimé avec succès.');
    }

    public function reset()
    {
        \App\Models\Back\Batiment::query()->delete();

        return redirect()
            ->route('back.batiments.index')
            ->with('success', 'Tous les bâtiments ont été supprimés.');
    }
}
