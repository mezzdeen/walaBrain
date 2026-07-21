<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Enums\OrganizationPermission;
use App\Modules\Core\Enums\OrganizationRole;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\OrganizationRoles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Lets a super admin inspect and adjust the roles a given organization owns, and
 * assign them to its members.
 */
class OrganizationRoleController extends Controller
{
    /**
     * Show the organization's roles and who holds them.
     */
    public function index(Organization $organization): Response
    {
        Gate::authorize('manageRoles', $organization);

        return OrganizationRoles::within($organization, fn (): Response => Inertia::render('super/organizations/roles', [
            'organization' => $organization->only(['id', 'name']),
            'roles' => $this->rolesFor($organization)
                ->withCount('permissions')
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Role $role): array => [
                    ...$role->only(['id', 'name', 'permissions_count']),
                    'protected' => OrganizationRole::tryFrom($role->name)?->isProtected() ?? false,
                ]),
            // Eager loaded inside the organization's context, so the relation
            // captures this team rather than querying once per member.
            'members' => $organization->users()->with('roles')->get(['users.id', 'first_name', 'last_name', 'email'])
                ->map(fn (User $member): array => [
                    ...$member->only(['id', 'first_name', 'last_name', 'full_name', 'email']),
                    'roles' => $member->roles->pluck('name')->all(),
                ]),
            'permissionGroups' => $this->permissionGroups(),
        ]));
    }

    /**
     * Replace the roles a member holds in this organization.
     */
    public function update(Request $request, Organization $organization, User $user): RedirectResponse
    {
        Gate::authorize('updateRoles', $user);

        // Scope rather than permission, and 404 rather than 403 on purpose: the
        // screen only ever lists members, so a user from outside the
        // organization is not a resource this route has.
        abort_unless($user->belongsToOrganization($organization), 404);

        $validated = $request->validate([
            'roles' => ['array'],
            'roles.*' => [
                'string',
                // Restricted to this organization's own roles, so a role
                // belonging to another one can not be assigned by id.
                Rule::in($this->rolesFor($organization)->pluck('name')),
            ],
        ]);

        // syncRoles writes the pivot against the current team, so it has to run
        // inside the organization's context or the assignment lands nowhere.
        OrganizationRoles::within(
            $organization,
            fn () => $user->syncRoles($validated['roles'] ?? []),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('core::roles.member_updated')]);

        return back();
    }

    /**
     * @return Builder<Role>
     */
    private function rolesFor(Organization $organization): Builder
    {
        return Role::query()
            ->where('guard_name', 'web')
            ->where('organization_id', $organization->getKey());
    }

    /**
     * @return array<string, list<string>>
     */
    private function permissionGroups(): array
    {
        $groups = [];

        foreach (OrganizationPermission::cases() as $permission) {
            $groups[$permission->group()][] = $permission->value;
        }

        return $groups;
    }
}
