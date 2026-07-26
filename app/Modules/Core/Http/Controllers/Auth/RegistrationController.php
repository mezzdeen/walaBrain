<?php

namespace App\Modules\Core\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Modules\Core\Http\Controllers\InvitationController;
use App\Modules\Core\Http\Requests\RegisterRequest;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Contracts\CreatesNewUsers;

/**
 * Creating an account without an invitation, when the platform allows it.
 *
 * Written here rather than switched on through Fortify's `registration`
 * feature. Fortify decides whether to register those routes when it boots, from
 * the static `config/fortify.php`; that decision cannot follow a switch held in
 * the database without reading it during configuration, which would break
 * `config:cache`. The middleware on these routes can, so the routes are ours.
 * The super platform's login is written out for a comparable reason.
 *
 * The counterpart is {@see InvitationController},
 * which is the only way in when the platform is closed. The difference between
 * the two is what has been established by the time the account exists: there, a
 * token that was mailed to the address, so the account is verified on the spot
 * and already has an organization. Here, nothing yet — hence the mail, and
 * hence the organization arriving only once it is answered.
 */
class RegistrationController extends Controller
{
    /**
     * Show the sign-up form.
     */
    public function create(): Response
    {
        return Inertia::render('auth/register', [
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]);
    }

    /**
     * Create the account and send its owner to confirm their address.
     *
     * They are signed in before they have verified anything, which is
     * deliberate: `verified` middleware holds them to the confirmation screen
     * and nothing else, and being signed in is what lets them ask for another
     * mail without typing their password again.
     */
    public function store(RegisterRequest $request, CreatesNewUsers $creator): RedirectResponse
    {
        // The whole input rather than `validated()`, because the creator
        // validates again and its `confirmed` rule needs the confirmation
        // field, which `validated()` drops — it is a check, not a value. What
        // reaches the database is the handful of attributes the creator names.
        $user = $creator->create($request->all());

        // Sends the verification mail, and is what
        // {@see \App\Modules\Core\Listeners\ProvisionOrganizationForNewUser}
        // eventually hangs off, by way of the `Verified` event it leads to.
        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return to_route('verification.notice');
    }
}
