<?php

use App\Modules\Core\Http\Controllers\InvitationController;
use App\Modules\Core\Http\Controllers\OrganizationSwitchController;
use App\Modules\Core\Http\Controllers\Settings\OrganizationRoleSettingsController;
use App\Modules\Core\Http\Controllers\Settings\ProfileController;
use App\Modules\Core\Http\Controllers\Settings\SecurityController;
use App\Modules\Core\Support\OrganizationContext;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Company Platform Routes
|--------------------------------------------------------------------------
|
| Routes the Core module contributes to the company platform. Module route
| files are loaded outside the framework's own routing group, so the `web`
| middleware has to be declared here rather than inherited.
|
*/

// With self-registration switched off, an invitation link is the only way into
// the application, so these have to be reachable while signed out.
Route::middleware(['web', 'guest'])->group(function () {
    Route::get('invitations/{token}', [InvitationController::class, 'show'])->name('invitations.show');
    Route::post('invitations/{token}', [InvitationController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('invitations.store');
});

// The guard is named rather than left to the default, which `actingAs` and any
// future middleware can repoint. It is what keeps a platform admin out of the
// company platform: they would otherwise satisfy `auth`, and the `Gate::before`
// bypass in AppServiceProvider would then wave them past every policy here.
Route::middleware(['web', 'auth:web'])->group(function () {
    Route::put('organizations/{organization}/switch', [OrganizationSwitchController::class, 'update'])
        ->name('organizations.switch');

    // Deliberately outside the `organization` middleware: this is the page it
    // redirects to. Bounced the other way for anyone who does have one, so it
    // cannot become a dead end someone reaches by bookmark.
    Route::get('no-organization', function () {
        return OrganizationContext::current() !== null
            ? to_route('dashboard')
            : Inertia::render('no-organization');
    })->name('organizations.none');

    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Authorized by RolePolicy rather than the `permission:` middleware: holding
    // the permission is only half of it, the role also has to belong to the
    // organization the request is acting on.
    Route::middleware('organization')->name('settings.')->group(function () {
        Route::get('settings/roles', [OrganizationRoleSettingsController::class, 'index'])->name('roles.index');
        Route::post('settings/roles', [OrganizationRoleSettingsController::class, 'store'])->name('roles.store');
        Route::put('settings/roles/{role}', [OrganizationRoleSettingsController::class, 'update'])->name('roles.update');
        Route::delete('settings/roles/{role}', [OrganizationRoleSettingsController::class, 'destroy'])->name('roles.destroy');
    });
});

// Closing an account and changing credentials are held to a verified address:
// the address is what a password reset and every security notice go to.
Route::middleware(['web', 'auth:web', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->middleware(RequirePassword::class)
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/appearance')->name('appearance.edit');
});

// Advertises where passkeys are enrolled and managed, for password managers
// that look it up. Unauthenticated on purpose: it only ever returns a URL.
Route::middleware('web')->get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
