<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Front\HomeController;
use App\Http\Controllers\Front\CarteController;
use App\Http\Controllers\Front\DemoController;
use App\Http\Controllers\Front\RechercheController;


Route::name('front.')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/carte', [CarteController::class, 'index'])->name('carte');
    Route::get('/demo', [DemoController::class, 'index'])->name('demo');
        Route::get('/recherche', [RechercheController::class, 'index'])->name('recherche');

});


