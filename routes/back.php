<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Back\DashboardController;
use App\Http\Controllers\Back\AdresseController;
use App\Http\Controllers\Back\BatimentController;
use App\Http\Controllers\Back\CoproprieteController;
use App\Http\Controllers\Back\SyndicController;
use App\Http\Controllers\Back\RechercheController;
use App\Http\Controllers\Back\ImportCsvController;
use App\Http\Controllers\Back\NotificationController;
use App\Http\Controllers\Back\MaintenanceController;
use App\Http\Controllers\Back\CsvImportController;

Route::middleware(['auth', 'verified', 'is_admin'])
    ->prefix('back')
    ->name('back.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // =========================
        // ADRESSES
        // =========================
        Route::delete('adresses/reset/all', [AdresseController::class, 'reset'])
            ->name('adresses.reset');

        Route::resource('adresses', AdresseController::class);


        // =========================
        // BÂTIMENTS
        // =========================
        Route::delete('batiments/reset/all', [BatimentController::class, 'reset'])
            ->name('batiments.reset');

        Route::resource('batiments', BatimentController::class);



        Route::delete('coproprietes/reset/all', [CoproprieteController::class, 'reset'])
            ->name('coproprietes.reset');

        Route::resource('coproprietes', CoproprieteController::class);


        Route::delete('syndics/reset/all', [SyndicController::class, 'reset'])
            ->name('syndics.reset');

        Route::get('syndics', [SyndicController::class, 'index'])->name('syndics.index');

        Route::get('syndics/create', [SyndicController::class, 'create'])->name('syndics.create');

        Route::post('syndics', [SyndicController::class, 'store'])->name('syndics.store');

        Route::get('syndics/{syndic}', [SyndicController::class, 'show'])->name('syndics.show');

        Route::get('syndics/{syndic}/edit', [SyndicController::class, 'edit'])->name('syndics.edit');

        Route::put('syndics/{syndic}', [SyndicController::class, 'update'])->name('syndics.update');

        Route::delete('syndics/{syndic}', [SyndicController::class, 'destroy'])->name('syndics.destroy');



        Route::get('recherches', [RechercheController::class, 'index'])
            ->name('recherches.index');

        Route::get('recherches/create', [RechercheController::class, 'create'])
            ->name('recherches.create');

        Route::post('recherches/search', [RechercheController::class, 'search'])
            ->name('recherches.search');

        /*
|--------------------------------------------------------------------------
| RESET GLOBAL
|--------------------------------------------------------------------------
*/

        Route::delete('recherches/reset/all', [RechercheController::class, 'reset'])
            ->name('recherches.reset');

        /*
|--------------------------------------------------------------------------
| ROUTES PAR RECHERCHE
|--------------------------------------------------------------------------
*/

        Route::get('recherches/{recherche}', [RechercheController::class, 'show'])
            ->name('recherches.show');

        Route::delete('recherches/{recherche}', [RechercheController::class, 'destroy'])
            ->name('recherches.destroy');





        Route::get('imports', [ImportCsvController::class, 'index'])->name('imports.index');
        Route::get('imports/create', [ImportCsvController::class, 'create'])->name('imports.create');
        Route::post('imports', [ImportCsvController::class, 'store'])->name('imports.store');
        Route::delete('imports/{importCsv}', [ImportCsvController::class, 'destroy'])->name('imports.destroy');



        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [NotificationController::class, 'index'])->name('index');
            Route::get('/create', [NotificationController::class, 'create'])->name('create');
            Route::post('/', [NotificationController::class, 'store'])->name('store');
            Route::get('/{notification}/edit', [NotificationController::class, 'edit'])->name('edit');
            Route::put('/{notification}', [NotificationController::class, 'update'])->name('update');
            Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('destroy');
            Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('markAllRead');
            Route::post('/{id}/mark-read', [NotificationController::class, 'markAsRead'])->name('markRead');
        });

        //  // Route API pour fetch les notifications (dans routes/web.php ou api.php)



      Route::prefix('csv-imports')->name('csv-imports.')->group(function () {

    Route::get('/', [CsvImportController::class, 'index'])
        ->name('index');

  Route::get('/download/{systeme}/{id}', [CsvImportController::class, 'download'])
    ->name('download');

    Route::delete('/{import}', [CsvImportController::class, 'destroy'])
        ->name('destroy');

    Route::delete('/bulk/delete', [CsvImportController::class, 'bulkDelete'])
        ->name('bulk-delete');

    Route::delete('/reset/all', [CsvImportController::class, 'reset'])
        ->name('reset');
});
        // routes/back.php
        Route::prefix('maintenance')->name('maintenance.')->group(function () {
            Route::get('/', [MaintenanceController::class, 'index'])->name('index');
            Route::delete('/recherches', [MaintenanceController::class, 'clearRecherches'])->name('recherches.clear');
            Route::delete('/cache', [MaintenanceController::class, 'clearCache'])->name('cache.clear');
            Route::delete('/jobs', [MaintenanceController::class, 'clearJobs'])->name('jobs.clear');
        });
    });

Route::get('/api/notifications/unread', [NotificationController::class, 'fetchUnread'])->name('api.notifications.unread');
