<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Back\BlockedIdentityController;
use App\Http\Controllers\Back\UserController;

Route::middleware(['auth', 'verified', 'is_admin'])
    ->prefix('admin/security')
    ->name('admin.security.')
    ->group(function () {

        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::get('/create', [UserController::class, 'create'])->name('create');
            Route::post('/', [UserController::class, 'store'])->name('store');

            Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
            Route::put('/{user}', [UserController::class, 'update'])->name('update');
            Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
            
            // ✅ ROUTE MANQUANTE POUR LA SUPPRESSION EN MASSE
            Route::delete('/bulk-delete', [UserController::class, 'bulkDelete'])->name('bulkDelete');

            Route::post('/give-credits', [UserController::class, 'giveCredits'])->name('giveCredits');
            Route::post('/remove-credits', [UserController::class, 'removeCredits'])->name('removeCredits');

            // ⚠️ Ces routes fonctionnent avec l'ID dans l'URL
            Route::post('/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('toggleActive');
            Route::post('/{user}/make-admin', [UserController::class, 'makeAdmin'])->name('makeAdmin');
            Route::post('/{user}/remove-admin', [UserController::class, 'removeAdmin'])->name('removeAdmin');
            Route::post('/{user}/verify-email', [UserController::class, 'verifyEmail'])->name('verifyEmail');
            Route::post('/{user}/toggle-otp-bypass', [UserController::class, 'toggleOtpBypass'])->name('toggleOtpBypass');
            Route::post('/{user}/ban', [UserController::class, 'ban'])->name('ban');
        });

        Route::prefix('blocked')->name('blocked.')->group(function () {
            Route::get('/', [BlockedIdentityController::class, 'index'])->name('index');
            Route::post('/', [BlockedIdentityController::class, 'store'])->name('store');
            Route::post('/{blockedIdentity}/toggle', [BlockedIdentityController::class, 'toggle'])->name('toggle');
            Route::delete('/{blockedIdentity}', [BlockedIdentityController::class, 'destroy'])->name('destroy');
        });
    });