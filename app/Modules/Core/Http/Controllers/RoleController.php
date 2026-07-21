<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Enums\SuperPermission;
use App\Modules\Core\Enums\SuperRole;
use App\Modules\Core\Http\Requests\StoreRoleRequest;
use App\Modules\Core\Http\Requests\UpdateRoleRequest;
use App\Modules\Core\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manages the global roles of the super admin platform.
 *
 * Scoped to `super` guard roles with no organization; the roles an organization
 * owns are managed by {@see OrganizationRoleController} instead.
 */
class RoleController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Role::class);
    }

    /**
     * Show a listing of the platform roles.
     */
    public function index(): Response
    {
        return Inertia::render('super/roles/index', [
            // `id` stays in the column list: it is what `hash_id` is derived
            // from, and a row loaded without it has no address to link to.
            'roles' => $this->platformRoles()
                ->withCount(['permissions', 'users'])
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Role $role): array => [
                    ...$role->only(['hash_id', 'name', 'permissions_count', 'users_count']),
                    'protected' => $this->isProtected($role),
                ]),
        ]);
    }

    /**
     * Show the form for creating a new role.
     */
    public function create(): Response
    {
        return Inertia::render('super/roles/create', [
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    /**
     * Store a newly created role.
     */
    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = Role::create([
            'name' => $request->validated('name'),
            'guard_name' => 'super',
            'organization_id' => null,
        ]);

        $role->syncPermissions($request->permissions());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('core::roles.created')]);

        return to_route('super.roles.index');
    }

    /**
     * Show the form for editing the given role.
     */
    public function edit(Role $role): Response
    {
        $this->authorizeManaging($role);

        return Inertia::render('super/roles/edit', [
            'role' => [
                ...$role->only(['hash_id', 'name']),
                'permissions' => $role->permissions->pluck('name')->all(),
                'protected' => $this->isProtected($role),
            ],
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    /**
     * Update the given role.
     */
    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->authorizeManaging($role);

        // The super admin role is what grants the ability to manage roles at
        // all. Letting it be renamed or narrowed is a way to lock everyone out.
        abort_if($this->isProtected($role), 403);

        $role->update(['name' => $request->validated('name')]);
        $role->syncPermissions($request->permissions());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('core::roles.updated')]);

        return to_route('super.roles.index');
    }

    /**
     * Remove the given role.
     */
    public function destroy(Role $role): RedirectResponse
    {
        $this->authorizeManaging($role);

        abort_if($this->isProtected($role), 403);

        $role->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('core::roles.deleted')]);

        return to_route('super.roles.index');
    }

    /**
     * Refuse anything that is not a global role of the admin platform, so an
     * organization's roles cannot be reached through these screens.
     *
     * Stays here rather than moving into {@see RolePolicy} for two reasons: a
     * super admin passes every gate through the `Gate::before` bypass, so a
     * policy could not enforce it against them; and the answer is 404 rather
     * than 403 on purpose, so these screens do not disclose that the role
     * exists. The policy answers the permission question, this answers scope.
     */
    private function authorizeManaging(Role $role): void
    {
        abort_unless($role->guard_name === 'super' && $role->organization_id === null, 404);
    }

    private function isProtected(Role $role): bool
    {
        return SuperRole::tryFrom($role->name)?->isProtected() ?? false;
    }

    /**
     * @return Builder<Role>
     */
    private function platformRoles(): Builder
    {
        return Role::query()->where('guard_name', 'super')->whereNull('organization_id');
    }

    /**
     * The permission catalogue, grouped by the subject it acts on, for the
     * checkbox matrix on the create and edit forms.
     *
     * @return array<string, list<string>>
     */
    private function permissionGroups(): array
    {
        $groups = [];

        foreach (SuperPermission::cases() as $permission) {
            $groups[$permission->group()][] = $permission->value;
        }

        return $groups;
    }
}
