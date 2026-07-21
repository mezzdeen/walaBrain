<?php

namespace App\Modules\Core\Http\Middleware;

use App\Modules\Core\Models\User;
use App\Modules\Core\Support\BrandColor;
use App\Modules\Core\Support\OrganizationContext;
use App\Modules\Core\Support\PermissionTeam;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetOrganizationContext
{
    /**
     * Point spatie's team context at the organization the request is acting on.
     *
     * Nothing below this middleware can check a permission correctly until the
     * team is set: roles are scoped to a team, so an unset one resolves to no
     * roles at all rather than to an error.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // The admin platform is decided by path rather than by which guard
        // happens to be authenticated, because a browser can hold sessions on
        // both at once — a super admin who is also a company user must not
        // inherit that user's organization while administering the platform.
        if ($request->is('super', 'super/*')) {
            // Opened up rather than merely cleared: the context is static, so a
            // company request earlier in the same process would otherwise hand
            // this one an organization belonging to a different user entirely.
            // The admin platform administers organizations instead of acting
            // inside one, so it reads across all of them by design.
            OrganizationContext::useGlobal();
            setPermissionsTeamId(PermissionTeam::SUPER);
            $this->forgetLoadedRoles($request->user('super'));

            // The platform administers organizations rather than belonging to
            // one, so it keeps the application's own colours.
            View::share('brandColorCss', '');

            return $next($request);
        }

        $user = $request->user('web');

        $organization = $user instanceof User ? OrganizationContext::resolve($user) : null;

        OrganizationContext::use($organization);

        $this->forgetLoadedRoles($user);

        // Shared with the Blade root view rather than only as an Inertia prop:
        // the stylesheet has to be in the document before the first paint, or
        // the page renders in the default colours and then jumps to the brand.
        View::share('brandColorCss', BrandColor::css($organization?->color));

        return $next($request);
    }

    /**
     * Drop any roles and permissions already loaded on the identity.
     *
     * Roles are read through a team-scoped relation, and Eloquent caches a
     * relation once it is loaded. An identity carried over from an earlier team
     * — a reused instance under Octane, or the same model across requests in a
     * test — would otherwise answer permission checks with the previous
     * organization's roles. Costs nothing: the relation is reloaded lazily, and
     * only if something actually asks.
     */
    private function forgetLoadedRoles(mixed $identity): void
    {
        if ($identity instanceof Model) {
            $identity->unsetRelation('roles')->unsetRelation('permissions');
        }
    }
}
