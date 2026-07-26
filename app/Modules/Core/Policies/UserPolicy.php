<?php

namespace App\Modules\Core\Policies;

use App\Modules\Core\Enums\SuperPermission;
use App\Modules\Core\Models\Admin;
use App\Modules\Core\Models\User;

/**
 * Authorizes acting on a user account: the platform looking users up, and a
 * user administering their own account.
 */
class UserPolicy
{
    /**
     * Determine whether the identity can look users up.
     *
     * Gated on creating organizations rather than a directory permission of its
     * own: the only caller is the owner field on the organization create form,
     * and keeping the two together stops the lookup becoming a general user
     * directory for anyone who happens to hold a lesser permission.
     */
    public function viewAny(User|Admin $identity): bool
    {
        return $identity instanceof Admin
            && $identity->can(SuperPermission::CreateOrganizations->value);
    }

    /**
     * Determine whether the identity can change the account's own settings.
     *
     * Self only. Neither platform has a screen for editing someone else's
     * profile or credentials, and an admin who could would be able to take over
     * a company account outright.
     */
    public function updateProfile(User|Admin $identity, User $user): bool
    {
        return $identity instanceof User && $identity->is($user);
    }

    /**
     * Determine whether the identity can close the account.
     */
    public function deleteAccount(User|Admin $identity, User $user): bool
    {
        return $identity instanceof User && $identity->is($user);
    }

    /**
     * Determine whether the identity can change which roles the user holds
     * inside an organization.
     *
     * A platform ability: the screen it backs lives on the admin platform, and
     * the membership check that pairs with it stays in the controller so a
     * non-member reads as not found rather than forbidden.
     */
    public function updateRoles(User|Admin $identity, User $user): bool
    {
        return $identity instanceof Admin
            && $identity->can(SuperPermission::ManageOrganizationRoles->value);
    }
}
