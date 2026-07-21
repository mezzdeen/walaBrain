<?php

namespace App\Modules\Core\Http\Middleware;

use App\Modules\Core\Support\PlatformSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps the sign-up screen behind the platform's registration switch.
 *
 * A 404 rather than a 403: when the platform is closed there is no sign-up to
 * be forbidden from. A 403 would confirm the page exists and is merely barred,
 * which invites someone to keep trying it.
 *
 * Only the two sign-up routes carry this. Confirming an address deliberately
 * does not, so closing the platform stops new accounts being made without
 * stranding anyone who made one while it was open.
 */
class EnsureRegistrationIsOpen
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(PlatformSettings::registrationIsOpen(), 404);

        return $next($request);
    }
}
