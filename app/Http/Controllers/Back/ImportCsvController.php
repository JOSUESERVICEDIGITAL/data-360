<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Http\Requests\Back\ImportCsvRequest;
use App\Models\Back\ImportCsv;

class ImportCsvController extends Controller
{
    public function index()
    {
        $imports = ImportCsv::latest()->paginate(15);

        return view('back.imports.index', compact('imports'));
    }

    public function create()
    {
        return view('back.imports.create');
    }

    public function store(ImportCsvRequest $request)
    {
        $file = $request->file('file');

        $path = $file->store('imports/csv', 'public');

        ImportCsv::create([
            'nom_fichier' => $file->getClientOriginalName(),
            'chemin' => $path,
            'total_lignes' => 0,
            'lignes_traitees' => 0,
            'statut' => 'en_attente',
        ]);

        return redirect()
            ->route('back.imports.index')
            ->with('success', 'Fichier importé avec succès.');
    }

    public function destroy(ImportCsv $importCsv)
    {
        $importCsv->delete();

        return redirect()
            ->route('back.imports.index')
            ->with('success', 'Import supprimé.');
    }
}