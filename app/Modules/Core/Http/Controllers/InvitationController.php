<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Http\Requests\AcceptInvitationRequest;
use App\Modules\Core\Models\OrganizationInvitation;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\OrganizationContext;
use App\Modules\Core\Support\OrganizationInvitations;
use App\Modules\Core\Support\OrganizationMembers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    /**
     * Show the invitation, or explain why it cannot be used.
     *
     * One link serves everyone the invitation might reach, so the branch it
     * lands on is decided here: a signed-in invitee confirms and joins, a
     * signed-in stranger is turned away, an existing account is sent to sign in
     * first, and a brand-new invitee finishes setting up their account.
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

        $user = Auth::guard('web')->user();

        if ($user instanceof User) {
            // A signed-in account can only take up an invitation addressed to
            // it; anyone else is looking at someone else's link.
            if (! $this->addresses($invitation, $user)) {
                return $this->refuse('wrong_account');
            }

            return Inertia::render('invitations/accept', [
                'organization' => ['name' => $invitation->organization->name],
                'role' => $invitation->role,
                'token' => $token,
            ]);
        }

        // A guest whose address already belongs to an account has nothing to
        // fill in: send them to sign in, remembering the link so they return
        // here to accept once they have.
        if ($this->addressIsTaken($invitation)) {
            return redirect()->guest(route('login'));
        }

        return Inertia::render('auth/accept-invitation', [
            'organization' => ['name' => $invitation->organization->name],
            'email' => $invitation->email,
            'token' => $token,
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]);
    }

    /**
     * Take up an invitation as the already-signed-in account it names.
     *
     * The other half of {@see self::store()}: where that creates a new account
     * for an invited address, this joins an existing one, so the organization
     * finally appears to a user who had to accept it first.
     */
    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = OrganizationInvitations::findPendingByToken($token);
        $user = $request->user();

        // Re-resolved and re-checked rather than trusted from `show`: the
        // invitation may have been withdrawn or expired since, and the request
        // must be the account it names.
        if (! $invitation instanceof OrganizationInvitation
            || ! $user instanceof User
            || ! $this->addresses($invitation, $user)) {
            return to_route('invitations.show', ['token' => $token]);
        }

        DB::transaction(function () use ($invitation, $user): void {
            OrganizationMembers::join($invitation->organization, $user, $invitation->role);
            $invitation->forceFill(['accepted_at' => now()])->save();
        });

        // Land them on the organization they just joined rather than whichever
        // one they were last acting on.
        OrganizationContext::switch($invitation->organization);

        return to_route('dashboard');
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

            OrganizationMembers::join($invitation->organization, $user, $invitation->role);

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
     * Whether the invitation is addressed to the given account.
     *
     * Compared in lower case: addresses are stored lowered on both sides, but a
     * mismatch here would silently hand one person's invitation to another, so
     * it is not left to that.
     */
    private function addresses(OrganizationInvitation $invitation, User $user): bool
    {
        return Str::lower($user->email) === Str::lower($invitation->email);
    }

    /**
     * Whether the invited address already belongs to an account.
     *
     * Deleted accounts count. They are invisible to an ordinary query but the
     * unique index on `users.email` still sees them, so reading a deleted
     * account's address as free renders a registration form whose submit can
     * only fail on the constraint.
     */
    private function addressIsTaken(OrganizationInvitation $invitation): bool
    {
        return User::withTrashed()->where('email', $invitation->email)->exists();
    }

    /**
     * Send an already-registered invitee to log in.
     */
    private function redirectToLogin(): RedirectResponse
    {
        return to_route('login')->with('status', __('core::invitations.already_registered'));
    }
}
