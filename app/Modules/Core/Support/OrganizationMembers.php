<?php

namespace App\Modules\Core\Support;

use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;

/**
 * Adds a user to an organization under one of its roles.
 *
 * The single place that knows what joining an organization is made of — a
 * membership plus a team-scoped role — so every route into one, ownership and
 * ordinary membership alike, is assembled the same way and cannot drift apart.
 */
final class OrganizationMembers
{
    /**
     * Make the user a member of the organization and give them the named role.
     *
     * Safe to call more than once, and additive by design: a user may belong to
     * any number of organizations, so nothing here touches the memberships or
     * roles they hold elsewhere.
     */
    public static function join(Organization $organization, User $user, string $roleName): void
    {
        $organization->users()->syncWithoutDetaching([$user->getKey()]);

        // Roles are team owned, so the role has to be resolved with the
        // permission team pointed at this organization. `assignRole` rather than
        // `syncRoles`: the latter would strip the roles the user holds in every
        // other organization.
        OrganizationRoles::within($organization, function () use ($user, $roleName): void {
            $user->assignRole($roleName);
        });
    }
}
