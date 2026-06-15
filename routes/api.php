<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CoproprieteController;

Route::prefix('coproprietes')->middleware(['auth:sanctum'])->group(function () {

    // Recherche par adresse
    Route::get('/rechercher', [CoproprieteController::class, 'rechercher']);

    // Enrichissement automatique (premier résultat)
    Route::get('/enrichir', [CoproprieteController::class, 'enrichir']);

    // Détail par ID RNIC
    Route::get('/{id}', [CoproprieteController::class, 'detail'])->where('id', '[0-9]+');

    // Vider le cache (superadmin)
    Route::delete('/cache', [CoproprieteController::class, 'viderCache'])
         ->middleware('role:superadmin');
});