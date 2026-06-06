<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Front\RechercheAvanceeController;

// ── Routes protégées : auth + plan premium ou enterprise ──
Route::middleware(['auth', 'advanced'])->group(function () {

    Route::post('/recherche/csv', [RechercheAvanceeController::class, 'csvImport'])
        ->name('front.recherche.csv');

    Route::get('/csv/suivi/{import}', [RechercheAvanceeController::class, 'suivi'])
        ->name('front.csv.suivi');

    Route::get('/csv/progress/{import}', [RechercheAvanceeController::class, 'progress'])
        ->name('front.csv.progress');

    Route::get('/csv/download/{import}', [RechercheAvanceeController::class, 'download'])
        ->name('front.csv.download');

});

// ── Template CSV : accessible sans restriction ──
Route::get('/recherche/csv/template', [RechercheAvanceeController::class, 'csvTemplate'])
    ->name('front.recherche.csv.template');
