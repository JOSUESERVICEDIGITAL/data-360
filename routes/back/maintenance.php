<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Back\MaintenanceController;

Route::prefix('back/maintenance')
    ->name('back.maintenance.')
    ->middleware(['auth', 'verified', 'is_admin'])
    ->group(function () {

        Route::get('/', [MaintenanceController::class, 'index'])
            ->name('index');

        Route::delete('/recherches', [MaintenanceController::class, 'clearRecherches'])
            ->name('recherches.clear');

        Route::delete('/cache', [MaintenanceController::class, 'clearCache'])
            ->name('cache.clear');

        Route::delete('/jobs', [MaintenanceController::class, 'clearJobs'])
            ->name('jobs.clear');

        Route::post('/optimize', [MaintenanceController::class, 'optimize'])
            ->name('optimize');
    });