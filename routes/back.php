<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Back\DashboardController;
use App\Http\Controllers\Back\AdresseController;
use App\Http\Controllers\Back\BatimentController;
use App\Http\Controllers\Back\CoproprieteController;
use App\Http\Controllers\Back\SyndicController;
use App\Http\Controllers\Back\RechercheController;
use App\Http\Controllers\Back\ImportCsvController;
use App\Http\Controllers\Back\UserController;

Route::middleware(['auth'])
    ->prefix('back')
    ->name('back.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('adresses', [AdresseController::class, 'index'])->name('adresses.index');
        Route::get('adresses/create', [AdresseController::class, 'create'])->name('adresses.create');
        Route::post('adresses', [AdresseController::class, 'store'])->name('adresses.store');
        Route::get('adresses/{adresse}', [AdresseController::class, 'show'])->name('adresses.show');
        Route::get('adresses/{adresse}/edit', [AdresseController::class, 'edit'])->name('adresses.edit');
        Route::put('adresses/{adresse}', [AdresseController::class, 'update'])->name('adresses.update');
        Route::delete('adresses/{adresse}', [AdresseController::class, 'destroy'])->name('adresses.destroy');

        Route::get('batiments', [BatimentController::class, 'index'])->name('batiments.index');
        Route::get('batiments/create', [BatimentController::class, 'create'])->name('batiments.create');
        Route::post('batiments', [BatimentController::class, 'store'])->name('batiments.store');
        Route::get('batiments/{batiment}', [BatimentController::class, 'show'])->name('batiments.show');
        Route::get('batiments/{batiment}/edit', [BatimentController::class, 'edit'])->name('batiments.edit');
        Route::put('batiments/{batiment}', [BatimentController::class, 'update'])->name('batiments.update');
        Route::delete('batiments/{batiment}', [BatimentController::class, 'destroy'])->name('batiments.destroy');

        Route::get('coproprietes', [CoproprieteController::class, 'index'])->name('coproprietes.index');
        Route::get('coproprietes/create', [CoproprieteController::class, 'create'])->name('coproprietes.create');
        Route::post('coproprietes', [CoproprieteController::class, 'store'])->name('coproprietes.store');
        Route::get('coproprietes/{copropriete}', [CoproprieteController::class, 'show'])->name('coproprietes.show');
        Route::get('coproprietes/{copropriete}/edit', [CoproprieteController::class, 'edit'])->name('coproprietes.edit');
        Route::put('coproprietes/{copropriete}', [CoproprieteController::class, 'update'])->name('coproprietes.update');
        Route::delete('coproprietes/{copropriete}', [CoproprieteController::class, 'destroy'])->name('coproprietes.destroy');

        Route::get('syndics', [SyndicController::class, 'index'])->name('syndics.index');
        Route::get('syndics/create', [SyndicController::class, 'create'])->name('syndics.create');
        Route::post('syndics', [SyndicController::class, 'store'])->name('syndics.store');
        Route::get('syndics/{syndic}', [SyndicController::class, 'show'])->name('syndics.show');
        Route::get('syndics/{syndic}/edit', [SyndicController::class, 'edit'])->name('syndics.edit');
        Route::put('syndics/{syndic}', [SyndicController::class, 'update'])->name('syndics.update');
        Route::delete('syndics/{syndic}', [SyndicController::class, 'destroy'])->name('syndics.destroy');

        Route::get('recherches', [RechercheController::class, 'index'])->name('recherches.index');
        Route::get('recherches/create', [RechercheController::class, 'create'])->name('recherches.create');
        Route::post('recherches/search', [RechercheController::class, 'search'])->name('recherches.search');
        Route::get('recherches/{recherche}', [RechercheController::class, 'show'])->name('recherches.show');
        Route::delete('recherches/{recherche}', [RechercheController::class, 'destroy'])->name('recherches.destroy');

        Route::get('imports', [ImportCsvController::class, 'index'])->name('imports.index');
        Route::get('imports/create', [ImportCsvController::class, 'create'])->name('imports.create');
        Route::post('imports', [ImportCsvController::class, 'store'])->name('imports.store');
        Route::delete('imports/{importCsv}', [ImportCsvController::class, 'destroy'])->name('imports.destroy');


         Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::get('/create', [UserController::class, 'create'])->name('create');
            Route::post('/store', [UserController::class, 'store'])->name('store');
            Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
            Route::put('/{user}', [UserController::class, 'update'])->name('update');
        });
       
    });

    