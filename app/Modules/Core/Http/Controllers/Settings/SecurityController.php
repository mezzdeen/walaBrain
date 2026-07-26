<?php

namespace App\Modules\Core\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Modules\Core\Http\Requests\Settings\PasswordUpdateRequest;
use App\Modules\Core\Http\Requests\Settings\TwoFactorAuthenticationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class SecurityController extends Controller
{
    /**
     * Show the user's security settings page.
     */
    public function edit(TwoFactorAuthenticationRequest $request): Response
    {
        Gate::authorize('updateProfile', $request->user());

        $props = [
            'canManageTwoFactor' => Features::canManageTwoFactorAuthentication(),
            'canManagePasskeys' => Features::canManagePasskeys(),
            'passkeys' => Features::canManagePasskeys()
                ? $request->user()
                    ->passkeys()
                    ->select(['id', 'name', 'credential', 'created_at', 'last_used_at'])
                    ->latest()
                    ->get()
                    ->map(fn ($passkey) => [
                        'id' => $passkey->id,
                        'name' => $passkey->name,
                        'authenticator' => $passkey->authenticator,
                        'created_at_diff' => $passkey->created_at->diffForHumans(),
                        'last_used_at_diff' => $passkey->last_used_at?->diffForHumans(),
                    ])
                    ->values()
                    ->all()
                : [],
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ];

        if (Features::canManageTwoFactorAuthentication()) {
            $request->ensureStateIsValid();

            $props['twoFactorEnabled'] = $request->user()->hasEnabledTwoFactorAuthentication();
            $props['requiresConfirmation'] = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
        }

        return Inertia::render('settings/security', $props);
    }

    /**
     * Update the user's password.
     */
    public function update(PasswordUpdateRequest $request): RedirectResponse
    {
        Gate::authorize('updateProfile', $request->user());

        $user = $request->user();

        $user->update(['password' => $request->password]);

        // A changed password has to revoke the access the old one granted, or a
        // stolen session or remember-me cookie outlives the very act meant to
        // shut it out. Cycling the remember token invalidates every remember-me
        // cookie; dropping the user's other sessions closes the ones already
        // open. The current session is regenerated so it survives with a fresh
        // identifier rather than being caught by the purge.
        $user->forceFill(['remember_token' => Str::random(60)])->save();

        $this->invalidateOtherSessions($request);

        $request->session()->regenerate();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Password updated.')]);

        return back();
    }

    /**
     * Remove every session belonging to the user except the one making this
     * request.
     *
     * Possible with the database session driver, where the rows are the
     * application's to delete. On any other driver the remember-token cycle is
     * the part of the revocation that still holds.
     */
    private function invalidateOtherSessions(Request $request): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::connection(config('session.connection'))
            ->table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->where('id', '!=', $request->session()->getId())
            ->delete();
    }
}
