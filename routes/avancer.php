<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Front\RechercheAvanceeController;

Route::post('/recherche/csv', [RechercheAvanceeController::class, 'csvImport'])
    ->name('front.recherche.csv');

Route::get('/recherche/csv/template', [RechercheAvanceeController::class, 'csvTemplate'])
    ->name('front.recherche.csv.template');


    // Route
Route::get('/csv/suivi/{import}', [RechercheAvanceeController::class, 'suivi'])
    ->name('front.csv.suivi');

Route::get('/csv/progress/{import}', [RechercheAvanceeController::class, 'progress'])
    ->name('front.csv.progress');

    Route::get('/csv/download/{import}', [RechercheAvanceeController::class, 'download'])
    ->name('front.csv.download');
