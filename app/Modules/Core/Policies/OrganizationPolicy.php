<?php

namespace App\Modules\Core\Policies;

use App\Modules\Core\Enums\OrganizationPermission;
use App\Modules\Core\Enums\SuperPermission;
use App\Modules\Core\Models\Admin;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;

/**
 * Authorizes both platforms against an organization: a platform admin
 * administering any organization, and a company user acting on one they belong
 * to. Laravel resolves a policy by the model, so the two rule sets meet here
 * and every method branches on which identity is asking.
 *
 * The branch is written as an `instanceof` test rather than a guard name so a
 * `web` user can never satisfy a super platform permission, even if the
 * default guard were somehow wrong. The permission names do not overlap
 * between guards, but the type check means the answer does not depend on that.
 */
class OrganizationPolicy
{
    /**
     * Determine whether the identity can list organizations.
     *
     * Company users have no organization index — they act on the one they are
     * switched to — so this is a platform ability only.
     */
    public function viewAny(User|Admin $identity): bool
    {
        return $identity instanceof Admin
            && $identity->can(SuperPermission::ViewOrganizations->value);
    }

    /**
     * Determine whether the identity can view the organization.
     */
    public function view(User|Admin $identity, Organization $organization): bool
    {
        return $identity instanceof Admin
            ? $identity->can(SuperPermission::ViewOrganizations->value)
            : $identity->belongsToOrganization($organization)
                && $identity->can(OrganizationPermission::ViewOrganization->value);
    }

    /**
     * Determine whether the identity can create an organization.
     *
     * Organizations are provisioned from the platform only; a company user
     * cannot bring new tenants into existence.
     */
    public function create(User|Admin $identity): bool
    {
        return $identity instanceof Admin
            && $identity->can(SuperPermission::CreateOrganizations->value);
    }

    /**
     * Determine whether the identity can update the organization.
     */
    public function update(User|Admin $identity, Organization $organization): bool
    {
        return $identity instanceof Admin
            ? $identity->can(SuperPermission::UpdateOrganizations->value)
            : $identity->belongsToOrganization($organization)
                && $identity->can(OrganizationPermission::UpdateOrganization->value);
    }

    /**
     * Determine whether the identity can delete the organization.
     *
     * Deleting a tenant cascades to its roles and memberships, so it stays a
     * platform ability no matter what an organization grants its own owners.
     */
    public function delete(User|Admin $identity, Organization $organization): bool
    {
        return $identity instanceof Admin
            && $identity->can(SuperPermission::DeleteOrganizations->value);
    }

    /**
     * Determine whether the identity can administer the roles held inside the
     * organization, from the platform's own screens.
     */
    public function manageRoles(User|Admin $identity, Organization $organization): bool
    {
        return $identity instanceof Admin
            && $identity->can(SuperPermission::ManageOrganizationRoles->value);
    }

    /**
     * Determine whether the identity can invite people into the organization.
     *
     * A company ability only. The platform provisions an organization with its
     * owner and leaves the owner to staff it, so there is no super permission
     * that would let an admin add members from the outside.
     */
    public function inviteMembers(User|Admin $identity, Organization $organization): bool
    {
        return $identity instanceof User
            && $identity->belongsToOrganization($organization)
            && $identity->can(OrganizationPermission::InviteMembers->value);
    }

    /**
     * Determine whether the user can make the organization the one they are
     * acting on.
     *
     * Membership alone, deliberately: switching is navigation, and what a user
     * may then do is decided by the roles they hold inside that organization.
     * Admins have no company context to switch.
     */
    public function switchTo(User|Admin $identity, Organization $organization): bool
    {
        return $identity instanceof User
            && $identity->belongsToOrganization($organization);
    }
}
