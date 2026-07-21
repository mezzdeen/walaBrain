<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Enums\OrganizationPermission;
use App\Modules\Core\Enums\OrganizationRole;
use App\Modules\Core\Http\Requests\StoreOrganizationRoleRequest;
use App\Modules\Core\Http\Requests\UpdateOrganizationRoleRequest;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Support\OrganizationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Lets an organization manage its own roles from the company platform.
 *
 * Sits beside {@see OrganizationSettingsController} at the top level rather
 * than under `/settings`: roles belong to the organization, not to the
 * signed-in user's account, and grouping them with profile and password would
 * say otherwise.
 *
 * Everything here is scoped to the organization the request is acting on, which
 * the middleware has already resolved from the session.
 */
class OrganizationRoleSettingsController extends Controller
{
    /**
     * Show the roles the current organization owns.
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', Role::class);

        $organization = OrganizationContext::current();

        return Inertia::render('roles/index', [
            'roles' => Role::query()
                ->where('guard_name', 'web')
                ->where('organization_id', $organization->getKey())
                ->with('permissions')
                ->withCount('permissions')
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Role $role): array => [
                    ...$role->only(['hash_id', 'name', 'permissions_count']),
                    'permissions' => $role->permissions->pluck('name')->all(),
                    'protected' => OrganizationRole::tryFrom($role->name)?->isProtected() ?? false,
                ]),
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    /**
     * Store a new role for the current organization.
     */
    public function store(StoreOrganizationRoleRequest $request): RedirectResponse
    {
        Gate::authorize('create', Role::class);

        $organization = OrganizationContext::current();

        $role = Role::create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
            'organization_id' => $organization->getKey(),
        ]);

        $role->syncPermissions($request->permissions());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('core::roles.created')]);

        return to_route('roles.index');
    }

    /**
     * Update one of the current organization's roles.
     */
    public function update(UpdateOrganizationRoleRequest $request, Role $role): RedirectResponse
    {
        Gate::authorize('update', $role);

        $role->update(['name' => $request->validated('name')]);
        $role->syncPermissions($request->permissions());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('core::roles.updated')]);

        return to_route('roles.index');
    }

    /**
     * Delete one of the current organization's roles.
     */
    public function destroy(Role $role): RedirectResponse
    {
        Gate::authorize('delete', $role);

        $role->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('core::roles.deleted')]);

        return to_route('roles.index');
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
