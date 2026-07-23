<?php

namespace App\Modules\Core\Support;

use App\Modules\Core\Enums\OrganizationRole;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;

/**
 * Grants a user ownership of an organization.
 *
 * The single place that knows what "being the owner" is made of, so the two
 * routes into ownership — an existing account named on the create form, and an
 * invited account completing registration — cannot drift apart.
 */
final class OrganizationOwners
{
    /**
     * Make the user a member of the organization and give them its owner role.
     *
     * Safe to call more than once, and additive by design: a user may own or
     * belong to any number of organizations, so nothing here touches the
     * memberships or roles they hold elsewhere.
     */
    public static function assign(Organization $organization, User $user): void
    {
        OrganizationMembers::join($organization, $user, OrganizationRole::Owner->value);
    }
}
