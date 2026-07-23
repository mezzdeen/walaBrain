<?php

use App\Modules\Core\Http\Controllers\Auth\RegistrationController;
use App\Modules\Core\Http\Controllers\InvitationController;
use App\Modules\Core\Http\Controllers\MemberController;
use App\Modules\Core\Http\Controllers\MemberInvitationController;
use App\Modules\Core\Http\Controllers\MemberSearchController;
use App\Modules\Core\Http\Controllers\OrganizationRoleSettingsController;
use App\Modules\Core\Http\Controllers\OrganizationSettingsController;
use App\Modules\Core\Http\Controllers\OrganizationSwitchController;
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

// An invitation link is a way into the application that does not depend on
// self-registration being open, so these have to be reachable while signed out
// whichever way the platform is set.
//
// `show` is not `guest`-only: one link serves a brand-new invitee and an
// existing account alike, and the controller decides which they are. `store`
// creates the invited account, so it stays for guests; `accept` joins an
// account that already exists, so it is for the signed in.
Route::middleware('web')->group(function () {
    Route::get('invitations/{token}', [InvitationController::class, 'show'])->name('invitations.show');

    Route::post('invitations/{token}', [InvitationController::class, 'store'])
        ->middleware(['guest', 'throttle:10,1'])
        ->name('invitations.store');

    Route::post('invitations/{token}/accept', [InvitationController::class, 'accept'])
        ->middleware(['auth:web', 'throttle:10,1'])
        ->name('invitations.accept');
});

// The other way in, and only while the platform is open to it — `registration`
// 404s these when it is not. Not Fortify's registration feature: that is
// decided from static configuration at boot and so cannot follow a switch that
// lives in the database. See RegistrationController.
Route::middleware(['web', 'guest', 'registration'])->group(function () {
    Route::get('register', [RegistrationController::class, 'create'])->name('register');
    Route::post('register', [RegistrationController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('register.store');
});

// The guard is named rather than left to the default, which `actingAs` and any
// future middleware can repoint. It is what keeps a platform admin out of the
// company platform: they would otherwise satisfy `auth`, and the `Gate::before`
// bypass this module registers would then wave them past every policy here.
//
// `verified` covers the whole group rather than the few screens that used to
// name it: a confirmed address is the price of entry to the application, not a
// condition on its more sensitive corners. Nothing needed while confirming is
// in here — the notice, the resend and signing out are all Fortify's, and the
// language and appearance switchers work off a cookie.
Route::middleware(['web', 'auth:web', 'verified'])->group(function () {
    // `organization`: the dashboard is the tenant home and the page every
    // switch lands on, so it has nothing to show a user who belongs to no
    // organization. Here rather than in the application's own routes, because
    // an organization is this module's idea and so is the middleware that
    // insists on one.
    Route::inertia('dashboard', 'dashboard')
        ->middleware('organization')
        ->name('dashboard');

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

    // The administration screens, grouped in the sidebar under one heading.
    // Top level rather than under `/settings`, which is the signed-in user's own
    // account: the organization, its roles and its members are not preferences.
    //
    // The role routes are authorized by RolePolicy rather than the `permission:`
    // middleware: holding the permission is only half of it, the role also has
    // to belong to the organization the request is acting on.
    Route::middleware('organization')->group(function () {
        Route::get('organization', [OrganizationSettingsController::class, 'edit'])->name('organization.edit');
        Route::patch('organization', [OrganizationSettingsController::class, 'update'])->name('organization.update');

        Route::get('roles', [OrganizationRoleSettingsController::class, 'index'])->name('roles.index');
        Route::post('roles', [OrganizationRoleSettingsController::class, 'store'])->name('roles.store');
        Route::put('roles/{role}', [OrganizationRoleSettingsController::class, 'update'])->name('roles.update');
        Route::delete('roles/{role}', [OrganizationRoleSettingsController::class, 'destroy'])->name('roles.destroy');

        // Distinct from the `invitations.show`/`store`/`accept` routes above,
        // which take up an invitation by token. This is where one is issued from.
        Route::get('invitations', [MemberInvitationController::class, 'index'])->name('invitations.index');
        Route::post('invitations', [MemberInvitationController::class, 'store'])->name('invitations.manage.store');
        Route::delete('invitations/{invitation}', [MemberInvitationController::class, 'destroy'])->name('invitations.manage.destroy');
        Route::post('invitations/{invitation}/resend', [MemberInvitationController::class, 'resend'])->name('invitations.manage.resend');

        // The owner's roster of everyone in the organization. Its own screen and
        // permission, distinct from the invite form and its typeahead below.
        Route::get('members', [MemberController::class, 'index'])->name('members.index');

        // Backs the invitee field on the invite form. Gated on the same invite
        // permission, and confined to accounts not already in the organization.
        Route::get('members/search', MemberSearchController::class)
            ->middleware('throttle:30,1')
            ->name('members.search');
    });

    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
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
