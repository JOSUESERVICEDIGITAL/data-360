<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Back\NotificationController;
use App\Http\Controllers\PaymentController;
// use ZipArchive;
// use Illuminate\Support\Facades\Storage;


require __DIR__ . '/front.php';


use App\Http\Controllers\Front\UserDashboardController;

Route::get('/dashboard', [UserDashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');



    Route::get('/notifications', [NotificationController::class, 'userIndex'])->name('notifications.index');
    Route::get('/notifications/{id}', [NotificationController::class, 'show'])->name('notifications.show');
    Route::post('/notifications/{id}/mark-read', [NotificationController::class, 'markAsRead'])->name('notifications.markRead');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllRead');

    Route::post('/payment/process', [PaymentController::class, 'process'])->name('payment.process');

});



require __DIR__ . '/auth.php';
require __DIR__ . '/back.php';
require __DIR__ . '/users.php';
require __DIR__.'/back/maintenance.php';
require __DIR__.'/avancer.php';
require __DIR__ . '/superadmin.php';
require __DIR__ . '/api.php';

