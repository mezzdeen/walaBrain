<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Http\Requests\AcceptInvitationRequest;
use App\Modules\Core\Models\OrganizationInvitation;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\OrganizationInvitations;
use App\Modules\Core\Support\OrganizationOwners;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    /**
     * Show the invitation, or explain why it cannot be used.
     */
    public function show(string $token): Response|RedirectResponse
    {
        $invitation = OrganizationInvitations::findByToken($token);

        if (! $invitation instanceof OrganizationInvitation) {
            return $this->refuse('invalid');
        }

        if ($invitation->isAccepted()) {
            return $this->refuse('accepted');
        }

        if ($invitation->isExpired()) {
            return $this->refuse('expired');
        }

        // Someone registered this address between the invitation being sent and
        // the link being opened. There is nothing to fill in, so send them to
        // log in instead of offering a form that cannot succeed.
        if ($this->addressIsTaken($invitation)) {
            return $this->redirectToLogin();
        }

        return Inertia::render('auth/accept-invitation', [
            'organization' => ['name' => $invitation->organization->name],
            'email' => $invitation->email,
            'token' => $token,
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]);
    }

    /**
     * Create the invited account, hand it the organization, and sign it in.
     */
    public function store(AcceptInvitationRequest $request, string $token): RedirectResponse
    {
        $invitation = OrganizationInvitations::findPendingByToken($token);

        if (! $invitation instanceof OrganizationInvitation) {
            return to_route('invitations.show', ['token' => $token]);
        }

        // Re-checked rather than trusted from `show`: the two requests are
        // minutes apart and the address could have been claimed in between.
        if ($this->addressIsTaken($invitation)) {
            return $this->redirectToLogin();
        }

        $user = DB::transaction(function () use ($request, $invitation): User {
            $user = User::create([
                'first_name' => $request->validated('first_name'),
                'last_name' => $request->validated('last_name'),
                'email' => $invitation->email,
                'password' => $request->validated('password'),
            ]);

            // Reaching this form required a token that was only ever mailed to
            // this address, which is the same proof the verification mail asks
            // for. Making them prove it twice would be theatre.
            $user->forceFill(['email_verified_at' => now()])->save();

            OrganizationOwners::assign($invitation->organization, $user);

            $invitation->forceFill(['accepted_at' => now()])->save();

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(config('fortify.home'));
    }

    /**
     * Render the invitation page in its dead-end state.
     */
    private function refuse(string $reason): Response
    {
        return Inertia::render('auth/accept-invitation', ['reason' => $reason]);
    }

    /**
     * Whether the invited address already belongs to an account.
     */
    private function addressIsTaken(OrganizationInvitation $invitation): bool
    {
        return User::query()->where('email', $invitation->email)->exists();
    }

    /**
     * Send an already-registered invitee to log in.
     */
    private function redirectToLogin(): RedirectResponse
    {
        return to_route('login')->with('status', __('core::invitations.already_registered'));
    }
}
