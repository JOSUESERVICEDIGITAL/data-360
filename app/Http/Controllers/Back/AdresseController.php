<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Http\Requests\Back\AdresseRequest;
use App\Models\Back\Adresse;

class AdresseController extends Controller
{
    public function index()
    {
        $adresses = Adresse::latest()->paginate(15);

        return view('back.adresses.index', compact('adresses'));
    }

    public function create()
    {
        return view('back.adresses.create');
    }

    public function store(AdresseRequest $request)
    {
        Adresse::create($request->validated());

        return redirect()
            ->route('back.adresses.index')
            ->with('success', 'Adresse créée avec succès.');
    }

    public function show(Adresse $adresse)
    {
        $adresse->load('batiments.coproprietes.syndics', 'coproprietes.syndics', 'recherches');

        return view('back.adresses.show', compact('adresse'));
    }

    public function edit(Adresse $adresse)
    {
        return view('back.adresses.edit', compact('adresse'));
    }

    public function update(AdresseRequest $request, Adresse $adresse)
    {
        $adresse->update($request->validated());

        return redirect()
            ->route('back.adresses.show', $adresse)
            ->with('success', 'Adresse mise à jour avec succès.');
    }

    public function destroy(\App\Models\Back\Adresse $adresse)
    {
        $adresse->delete();

        return redirect()
            ->route('back.adresses.index')
            ->with('success', 'Adresse supprimée avec succès.');
    }

    public function reset()
    {
        \App\Models\Back\Adresse::query()->delete();

        return redirect()
            ->route('back.adresses.index')
            ->with('success', 'Toutes les adresses ont été supprimées.');
    }
}
