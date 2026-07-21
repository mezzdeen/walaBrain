<?php

namespace App\Modules\Core\Http\Middleware;

use App\Modules\Core\Support\OrganizationContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keep organization-scoped pages away from a user who has no organization.
 *
 * Membership can be revoked while someone is signed in, which leaves them
 * authenticated with nothing to act on. Every controller downstream is written
 * assuming an organization is present, so the first one to reach for its key
 * would fail on null. Sending them to an explanatory page instead keeps that
 * assumption honest and tells them why the application went empty.
 *
 * Not applied to the profile and security screens: those belong to the person,
 * not to any organization, and must stay reachable so they can sign out or
 * close the account.
 */
class RequiresOrganization
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (OrganizationContext::current() === null) {
            return redirect()->route('organizations.none');
        }

        return $next($request);
    }
}
