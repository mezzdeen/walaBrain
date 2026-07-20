<?php

use App\Modules\Core\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Modules\Core\Http\Controllers\MailDiagnosticsController;
use App\Modules\Core\Http\Controllers\OrganizationController;
use App\Modules\Core\Http\Controllers\OrganizationRoleController;
use App\Modules\Core\Http\Controllers\RoleController;
use App\Modules\Core\Http\Controllers\UserSearchController;
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
| Authentication is declared here; authorization is not. Every action is
| covered by a policy under App\Modules\Core\Policies, so the permission a
| route requires is read from the policy rather than from this file — one
| place to look, and one place to change. `auth:super` is load-bearing for
| that: it repoints the default guard, which is how the gate resolves the
| Admin rather than a company user.
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

        Route::resource('organizations', OrganizationController::class);

        // Backs the owner field on the organization create form. Gated on the
        // same permission as creating an organization rather than one of its
        // own, so it cannot become a general user directory.
        Route::get('users/search', UserSearchController::class)
            ->middleware('throttle:30,1')
            ->name('users.search');

        Route::get('organizations/{organization}/roles', [OrganizationRoleController::class, 'index'])
            ->name('organizations.roles.index');
        Route::put('organizations/{organization}/members/{user}/roles', [OrganizationRoleController::class, 'update'])
            ->name('organizations.members.roles.update');

        // Verifies the mail configuration end to end after it changes. GET so it
        // can be run by pasting the URL in a browser, which is the whole point
        // of a diagnostic — it has to work when the UI might not. That makes it
        // a side effect on a GET, and a prefetch can therefore fire it; the cost
        // of that is bounded to one throttled mail to the admin's own address.
        Route::get('diagnostics/mail', MailDiagnosticsController::class)
            ->middleware('throttle:6,1')
            ->name('diagnostics.mail');

        Route::resource('roles', RoleController::class)->except('show');
    });
});
