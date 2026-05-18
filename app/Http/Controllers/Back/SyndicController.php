<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Http\Requests\Back\SyndicRequest;
use App\Models\Back\Syndic;

class SyndicController extends Controller
{
    public function index()
    {
        $syndics = Syndic::withCount('coproprietes')
            ->latest()
            ->paginate(15);

        return view('back.syndics.index', compact('syndics'));
    }

    public function create()
    {
        return view('back.syndics.create');
    }

    public function store(SyndicRequest $request)
    {
        Syndic::create($request->validated());

        return redirect()
            ->route('back.syndics.index')
            ->with('success', 'Syndic créé avec succès.');
    }

    public function show(Syndic $syndic)
    {
        $syndic->load('coproprietes.adresse');

        return view('back.syndics.show', compact('syndic'));
    }

    public function edit(Syndic $syndic)
    {
        return view('back.syndics.edit', compact('syndic'));
    }

    public function update(SyndicRequest $request, Syndic $syndic)
    {
        $syndic->update($request->validated());

        return redirect()
            ->route('back.syndics.show', $syndic)
            ->with('success', 'Syndic mis à jour avec succès.');
    }

    public function destroy(Syndic $syndic)
    {
        $syndic->delete();

        return redirect()
            ->route('back.syndics.index')
            ->with('success', 'Syndic supprimé avec succès.');
    }

    public function reset()
    {
        Syndic::query()->delete();

        return redirect()
            ->route('back.syndics.index')
            ->with('success', 'Tous les syndics ont été supprimés.');
    }
}
