<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\Back\CsvImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CsvImportController extends Controller
{


public function index(Request $request)
{
    $perPage = (int) $request->input('per_page', 20);
    $perPage = in_array($perPage, [20,50,100,200]) ? $perPage : 20;

    /*
    |--------------------------------------------------------------------------
    | Nouveaux imports (BDD)
    |--------------------------------------------------------------------------
    */

    $nouveaux = CsvImport::with('user')
        ->latest()
        ->get()
        ->map(function ($i) {

            $i->systeme = 'nouveau';
            $i->nom_fichier = $i->filename_original;

            return $i;
        });

    /*
    |--------------------------------------------------------------------------
    | Anciens imports (scan du dossier)
    |--------------------------------------------------------------------------
    */

    $anciens = collect();

    $directory = storage_path('app/public/csv_imports');

    if (File::exists($directory)) {

        foreach (File::files($directory) as $file) {

            $anciens->push((object)[

                'id' => 'old-'.md5($file->getFilename()),

                'systeme' => 'ancien',

                'user' => null,

                'user_id' => null,

                'nom_fichier' => $file->getFilename(),

                'filename_result' => $file->getFilename(),

                'xlsx_content' => null,

                'statut' => 'termine',

                'total_lignes' => null,

                'lignes_traitees' => null,

                'erreur_message' => null,

                'created_at' => \Carbon\Carbon::createFromTimestamp(
                    $file->getMTime()
                ),

                'updated_at' => \Carbon\Carbon::createFromTimestamp(
                    $file->getMTime()
                ),

            ]);

        }

    }

    /*
    |--------------------------------------------------------------------------
    | Fusion
    |--------------------------------------------------------------------------
    */

    $imports = $nouveaux
        ->concat($anciens)
        ->sortByDesc('created_at')
        ->values();

    /*
    |--------------------------------------------------------------------------
    | Pagination manuelle
    |--------------------------------------------------------------------------
    */

    $page = LengthAwarePaginator::resolveCurrentPage();

    $items = $imports->slice(
        ($page-1)*$perPage,
        $perPage
    )->values();

    $imports = new LengthAwarePaginator(

        $items,

        $imports->count(),

        $perPage,

        $page,

        [
            'path' => request()->url(),
            'query' => request()->query(),
        ]

    );

    /*
    |--------------------------------------------------------------------------
    | Statistiques
    |--------------------------------------------------------------------------
    */

    $importStats = [

        'termine' => $imports->total(),

        'en_cours' => CsvImport::whereIn('statut',[
            'en_cours',
            'en_attente'
        ])->count(),

        'erreur' => CsvImport::where('statut','erreur')->count(),

    ];

    return view(
        'back.csv-imports.index',
        compact(
            'imports',
            'importStats',
            'perPage'
        )
    );
}
    public function download(string $systeme, string $id)
{
    /*
    |--------------------------------------------------------------------------
    | Nouveau système
    |--------------------------------------------------------------------------
    */
    if ($systeme === 'nouveau') {

        $import = CsvImport::findOrFail($id);

        if ($import->statut !== 'termine') {
            abort(404, 'Import non terminé.');
        }

        if (!empty($import->xlsx_content)) {

            $xlsxBinary = base64_decode($import->xlsx_content);

            $filename = $import->filename_result
                ?: ('import-'.$import->id.'-'.$import->created_at?->format('Ymd-His').'.xlsx');

            $tmpFile = tempnam(sys_get_temp_dir(), 'dr_import_');

            file_put_contents($tmpFile, $xlsxBinary);

            return response()->download(
                $tmpFile,
                $filename,
                [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ]
            )->deleteFileAfterSend(true);
        }

        abort(404, 'Le contenu XLSX est introuvable.');
    }

    /*
    |--------------------------------------------------------------------------
    | Ancien système
    |--------------------------------------------------------------------------
    */

    $directory = storage_path('app/public/csv_imports');

    if (!File::exists($directory)) {
        abort(404, 'Le dossier des anciens imports est introuvable.');
    }

    $files = File::files($directory);

    foreach ($files as $file) {

        $virtualId = 'old-' . md5($file->getFilename());

        if ($virtualId === $id) {

            return response()->download(
                $file->getRealPath(),
                $file->getFilename(),
                [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ]
            );

        }

    }

    abort(404, 'Ancien fichier introuvable.');
}
    public function destroy(CsvImport $import)
    {
        $import->delete();
        return back()->with('success', 'Import supprimé.');
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        if (!empty($ids)) {
            CsvImport::whereIn('id', $ids)->delete();
        }
        return back()->with('success', count($ids) . ' import(s) supprimé(s).');
    }

    public function reset()
    {
        CsvImport::truncate();
        return back()->with('success', 'Tous les imports ont été supprimés.');
    }
}
