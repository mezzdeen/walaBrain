<?php

use App\Modules\Core\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Super Admin Platform Routes
|--------------------------------------------------------------------------
|
| The admin platform lives behind the `/super` prefix and is protected by
| the dedicated `super` authentication guard (see config/auth.php). It is
| completely isolated from the company platform served by routes/web.php.
|
*/

Route::middleware('web')->prefix('super')->name('super.')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');

    Route::middleware('auth:super')->group(function () {
        Route::redirect('/', '/super/dashboard');
        Route::inertia('dashboard', 'super/dashboard')->name('dashboard');

        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    });
});
