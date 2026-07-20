<?php

use App\Modules\Core\Http\Controllers\InvitationController;
use App\Modules\Core\Http\Controllers\OrganizationSwitchController;
use App\Modules\Core\Http\Controllers\Settings\OrganizationRoleSettingsController;
use Illuminate\Support\Facades\Route;

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

Route::middleware(['web', 'auth'])->group(function () {
    Route::put('organizations/{organization}/switch', [OrganizationSwitchController::class, 'update'])
        ->name('organizations.switch');

    // Authorized by RolePolicy rather than the `permission:` middleware: holding
    // the permission is only half of it, the role also has to belong to the
    // organization the request is acting on.
    Route::name('settings.')->group(function () {
        Route::get('settings/roles', [OrganizationRoleSettingsController::class, 'index'])->name('roles.index');
        Route::post('settings/roles', [OrganizationRoleSettingsController::class, 'store'])->name('roles.store');
        Route::put('settings/roles/{role}', [OrganizationRoleSettingsController::class, 'update'])->name('roles.update');
        Route::delete('settings/roles/{role}', [OrganizationRoleSettingsController::class, 'destroy'])->name('roles.destroy');
    });
});
