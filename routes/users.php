<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Back\AdminUserCreditController;
use App\Http\Controllers\Back\BlockedIdentityController;

Route::middleware(['auth'])
    ->prefix('admin/security')
    ->name('admin.security.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | UTILISATEURS / CRÉDITS
        |--------------------------------------------------------------------------
        */

        Route::get('/users', [AdminUserCreditController::class, 'index'])
            ->name('users.index');

        Route::post('/users/give-credits', [AdminUserCreditController::class, 'giveCredits'])
            ->name('users.giveCredits');

        Route::post('/users/remove-credits', [AdminUserCreditController::class, 'removeCredits'])
            ->name('users.removeCredits');

        Route::post('/users/{user}/toggle-active', [AdminUserCreditController::class, 'toggleActive'])
            ->name('users.toggleActive');

        Route::post('/users/{user}/make-admin', [AdminUserCreditController::class, 'makeAdmin'])
            ->name('users.makeAdmin');

        Route::post('/users/{user}/remove-admin', [AdminUserCreditController::class, 'removeAdmin'])
            ->name('users.removeAdmin');


        /*
        |--------------------------------------------------------------------------
        | IDENTITÉS BLOQUÉES
        |--------------------------------------------------------------------------
        */

        Route::get('/blocked', [BlockedIdentityController::class, 'index'])
            ->name('blocked.index');

        Route::post('/blocked', [BlockedIdentityController::class, 'store'])
            ->name('blocked.store');

        Route::post('/blocked/{blockedIdentity}/toggle', [BlockedIdentityController::class, 'toggle'])
            ->name('blocked.toggle');

        Route::delete('/blocked/{blockedIdentity}', [BlockedIdentityController::class, 'destroy'])
            ->name('blocked.destroy');

        Route::post('/users/{user}/toggle-otp-bypass', [AdminUserCreditController::class, 'toggleOtpBypass'])
            ->name('users.toggleOtpBypass');
    });