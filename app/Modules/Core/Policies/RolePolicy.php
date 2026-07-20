<?php

namespace App\Modules\Core\Policies;

use App\Modules\Core\Enums\OrganizationPermission;
use App\Modules\Core\Enums\OrganizationRole;
use App\Modules\Core\Enums\SuperPermission;
use App\Modules\Core\Models\Admin;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\OrganizationContext;

/**
 * Authorizes role management on both platforms. The `roles` table is shared —
 * an organization's roles and the platform's global roles are rows in it — so
 * Laravel resolves both rule sets to this one policy and each method branches
 * on the identity asking.
 *
 * A policy rather than the `permission:` middleware, because on the company
 * side holding the permission is only half the question — the role also has to
 * belong to the organization the request is scoped to. The middleware can only
 * answer the first half, and a user with `roles.update` in their own
 * organization would otherwise be able to edit another organization's role by
 * id.
 *
 * The two branches are deliberately asymmetric. A super admin passes every
 * gate through the `Gate::before` bypass in `AppServiceProvider`, so anything
 * that must hold *even for them* cannot live here: {@see RoleController} keeps
 * the scope check (an organization role is 404 on the platform screens, so the
 * screens do not disclose that it exists) and the protected-role check (the
 * super admin role stays as provisioned, or the platform locks itself out).
 * Those are structural invariants that outrank permission. This policy answers
 * only the permission question for admins.
 */
class RolePolicy
{
    /**
     * Determine whether the identity can see roles.
     */
    public function viewAny(User|Admin $identity): bool
    {
        return $identity instanceof Admin
            ? $identity->can(SuperPermission::ViewRoles->value)
            : $this->inOrganization()
                && $identity->can(OrganizationPermission::ViewRoles->value);
    }

    /**
     * Determine whether the identity can create a role.
     */
    public function create(User|Admin $identity): bool
    {
        return $identity instanceof Admin
            ? $identity->can(SuperPermission::CreateRoles->value)
            : $this->inOrganization()
                && $identity->can(OrganizationPermission::CreateRoles->value);
    }

    /**
     * Determine whether the identity can update the given role.
     */
    public function update(User|Admin $identity, Role $role): bool
    {
        return $identity instanceof Admin
            ? $identity->can(SuperPermission::UpdateRoles->value)
            : $this->owns($role)
                && ! $this->isProtected($role)
                && $identity->can(OrganizationPermission::UpdateRoles->value);
    }

    /**
     * Determine whether the identity can delete the given role.
     */
    public function delete(User|Admin $identity, Role $role): bool
    {
        return $identity instanceof Admin
            ? $identity->can(SuperPermission::DeleteRoles->value)
            : $this->owns($role)
                && ! $this->isProtected($role)
                && $identity->can(OrganizationPermission::DeleteRoles->value);
    }

    /**
     * Whether the role belongs to the organization the request is acting on.
     *
     * Also rejects `super` guard roles, which share the table but belong to the
     * other platform entirely.
     */
    private function owns(Role $role): bool
    {
        $organization = OrganizationContext::current();

        return $organization !== null
            && $role->guard_name === 'web'
            && $role->organization_id === $organization->getKey();
    }

    private function inOrganization(): bool
    {
        return OrganizationContext::current() !== null;
    }

    /**
     * The owner role is what grants role management in the first place, so it
     * stays as provisioned.
     */
    private function isProtected(Role $role): bool
    {
        return OrganizationRole::tryFrom($role->name)?->isProtected() ?? false;
    }
}
