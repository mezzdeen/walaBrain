<?php

namespace App\Modules\Core\Policies;

use App\Modules\Core\Enums\OrganizationPermission;
use App\Modules\Core\Enums\OrganizationRole;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\OrganizationContext;

/**
 * Authorizes a company user managing the roles of the organization they are
 * currently acting on.
 *
 * A policy rather than the `permission:` middleware, because holding the
 * permission is only half the question — the role also has to belong to the
 * organization the request is scoped to. The middleware can only answer the
 * first half, and a user with `roles.update` in their own organization would
 * otherwise be able to edit another organization's role by id.
 */
class RolePolicy
{
    /**
     * Determine whether the user can see the organization's roles.
     */
    public function viewAny(User $user): bool
    {
        return $this->inOrganization()
            && $user->can(OrganizationPermission::ViewRoles->value);
    }

    /**
     * Determine whether the user can create a role.
     */
    public function create(User $user): bool
    {
        return $this->inOrganization()
            && $user->can(OrganizationPermission::CreateRoles->value);
    }

    /**
     * Determine whether the user can update the given role.
     */
    public function update(User $user, Role $role): bool
    {
        return $this->owns($role)
            && ! $this->isProtected($role)
            && $user->can(OrganizationPermission::UpdateRoles->value);
    }

    /**
     * Determine whether the user can delete the given role.
     */
    public function delete(User $user, Role $role): bool
    {
        return $this->owns($role)
            && ! $this->isProtected($role)
            && $user->can(OrganizationPermission::DeleteRoles->value);
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
