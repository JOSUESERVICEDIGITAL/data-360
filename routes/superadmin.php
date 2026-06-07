<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Back\SuperAdminController;

Route::middleware(['auth', 'verified'])->prefix('admin/superadmin')->name('admin.superadmin.')->group(function () {

    // ────────────────────────────────────────────────────────────
    // DASHBOARD
    // ────────────────────────────────────────────────────────────
    Route::get('/', [SuperAdminController::class, 'index'])
        ->name('index');

    // ────────────────────────────────────────────────────────────
    // UTILISATEURS
    // ────────────────────────────────────────────────────────────
    Route::prefix('users')->name('users.')->group(function () {

        Route::get('/', [SuperAdminController::class, 'users'])
            ->name('index');

        Route::post('/{user}/make-superadmin', [SuperAdminController::class, 'makeSuperAdmin'])
            ->name('make-superadmin');

        Route::post('/{user}/remove-superadmin', [SuperAdminController::class, 'removeSuperAdmin'])
            ->name('remove-superadmin');

        Route::post('/{user}/force-password-reset', [SuperAdminController::class, 'forcePasswordReset'])
            ->name('force-password-reset');

        Route::post('/{user}/impersonate', [SuperAdminController::class, 'impersonate'])
            ->name('impersonate');

        Route::post('/bulk-credits', [SuperAdminController::class, 'bulkCredits'])
            ->name('bulk-credits');

        Route::post('/bulk-plan', [SuperAdminController::class, 'bulkPlan'])
            ->name('bulk-plan');

        Route::get('/export', [SuperAdminController::class, 'exportUsers'])
            ->name('export');

    });

    // Stop impersonate (hors prefix users car utilisable depuis n'importe où)
    Route::post('/stop-impersonate', [SuperAdminController::class, 'stopImpersonate'])
        ->name('stop-impersonate');

    // ────────────────────────────────────────────────────────────
    // HISTORIQUES (JSON pour les modales)
    // ────────────────────────────────────────────────────────────
    Route::prefix('history')->name('history.')->group(function () {

        Route::get('/connexions', [SuperAdminController::class, 'connexionsHistory'])
            ->name('connexions');

        Route::get('/imports', [SuperAdminController::class, 'importsHistory'])
            ->name('imports');

    });

    // ────────────────────────────────────────────────────────────
    // PAIEMENTS
    // ────────────────────────────────────────────────────────────
    Route::prefix('payments')->name('payments.')->group(function () {

        Route::get('/', [SuperAdminController::class, 'payments'])
            ->name('index');

    });

    // ────────────────────────────────────────────────────────────
    // MAINTENANCE BASE DE DONNÉES
    // ────────────────────────────────────────────────────────────
    Route::prefix('purge')->name('purge.')->group(function () {

        Route::post('/recherches', [SuperAdminController::class, 'purgeRecherches'])
            ->name('recherches');

        Route::post('/imports', [SuperAdminController::class, 'purgeImports'])
            ->name('imports');

        Route::post('/sessions', [SuperAdminController::class, 'purgeSessions'])
            ->name('sessions');

        Route::post('/failed-jobs', [SuperAdminController::class, 'purgeFailedJobs'])
            ->name('failed-jobs');

        Route::post('/logs', [SuperAdminController::class, 'purgeLogs'])
            ->name('logs');

        Route::post('/cache', [SuperAdminController::class, 'purgeCache'])
            ->name('cache');

    });

    // ────────────────────────────────────────────────────────────
    // CACHE ARTISAN
    // ────────────────────────────────────────────────────────────
    Route::post('/cache/clear', [SuperAdminController::class, 'clearCache'])
        ->name('cache.clear');

    // ────────────────────────────────────────────────────────────
    // PERFORMANCES & MONITORING
    // ────────────────────────────────────────────────────────────
    Route::prefix('metrics')->name('metrics.')->group(function () {

        Route::get('/performance', [SuperAdminController::class, 'performanceMetrics'])
            ->name('performance');

        Route::get('/user-growth', [SuperAdminController::class, 'userGrowthStats'])
            ->name('user-growth');

        Route::get('/logs-info', [SuperAdminController::class, 'logsInfo'])
            ->name('logs-info');

        Route::get('/queue', [SuperAdminController::class, 'queueStats'])
            ->name('queue');

        Route::get('/db-stats', [SuperAdminController::class, 'dbStats'])
            ->name('db-stats');

    });

    // ────────────────────────────────────────────────────────────
    // NOTIFICATIONS GLOBALES
    // ────────────────────────────────────────────────────────────
    Route::post('/notifications/broadcast', [SuperAdminController::class, 'broadcastNotification'])
        ->name('notifications.broadcast');

    // ────────────────────────────────────────────────────────────
    // MAINTENANCE MODE
    // ────────────────────────────────────────────────────────────
    Route::prefix('maintenance')->name('maintenance.')->group(function () {

        Route::get('/status', [SuperAdminController::class, 'maintenanceStatus'])
            ->name('status');

        Route::post('/toggle', [SuperAdminController::class, 'toggleMaintenance'])
            ->name('toggle');

    });

});
