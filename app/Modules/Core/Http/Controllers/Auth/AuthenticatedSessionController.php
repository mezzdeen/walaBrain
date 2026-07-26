<?php

namespace App\Modules\Core\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the super admin login page.
     */
    public function create(): RedirectResponse|Response
    {
        if (Auth::guard('super')->check()) {
            return redirect()->route('super.dashboard');
        }

        return Inertia::render('super/auth/login');
    }

    /**
     * Authenticate a super admin against the super guard.
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('super')->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('super.dashboard'));
    }

    /**
     * Log the super admin out of the super guard.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('super')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('super.login');
    }
}
