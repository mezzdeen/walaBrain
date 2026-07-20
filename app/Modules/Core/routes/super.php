<?php

use App\Modules\Core\Enums\SuperPermission;
use App\Modules\Core\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Modules\Core\Http\Controllers\OrganizationController;
use App\Modules\Core\Http\Controllers\OrganizationRoleController;
use App\Modules\Core\Http\Controllers\RoleController;
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

        // Guarded per action rather than by hand-writing the seven routes, so
        // the URIs, names and verbs stay exactly what the resource registrar
        // produces. The guard is named explicitly on every check so it never
        // depends on which guard the auth middleware happened to make default.
        Route::resource('organizations', OrganizationController::class)
            ->middlewareFor(['index', 'show'], 'permission:'.SuperPermission::ViewOrganizations->value.',super')
            ->middlewareFor(['create', 'store'], 'permission:'.SuperPermission::CreateOrganizations->value.',super')
            ->middlewareFor(['edit', 'update'], 'permission:'.SuperPermission::UpdateOrganizations->value.',super')
            ->middlewareFor('destroy', 'permission:'.SuperPermission::DeleteOrganizations->value.',super');

        Route::middleware('permission:'.SuperPermission::ManageOrganizationRoles->value.',super')->group(function () {
            Route::get('organizations/{organization}/roles', [OrganizationRoleController::class, 'index'])
                ->name('organizations.roles.index');
            Route::put('organizations/{organization}/members/{user}/roles', [OrganizationRoleController::class, 'update'])
                ->name('organizations.members.roles.update');
        });

        Route::resource('roles', RoleController::class)->except('show')
            ->middlewareFor('index', 'permission:'.SuperPermission::ViewRoles->value.',super')
            ->middlewareFor(['create', 'store'], 'permission:'.SuperPermission::CreateRoles->value.',super')
            ->middlewareFor(['edit', 'update'], 'permission:'.SuperPermission::UpdateRoles->value.',super')
            ->middlewareFor('destroy', 'permission:'.SuperPermission::DeleteRoles->value.',super');
    });
});
