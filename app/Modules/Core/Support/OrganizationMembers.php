<?php

namespace App\Modules\Core\Support;

use App\Modules\Core\Exceptions\InvalidReportingLine;
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

    /**
     * Say who a member reports to in the organization, or that they report to
     * nobody.
     *
     * Three things a reporting line can never be, refused here rather than left
     * to the caller: a manager from outside the organization, which would cross
     * a boundary nothing else in the application crosses; somebody managing
     * themselves; and a loop, which would leave the line with no top and send
     * anything walking it round forever.
     *
     * @throws InvalidReportingLine
     */
    public static function setManager(Organization $organization, User $member, ?User $manager): void
    {
        if ($manager instanceof User) {
            if ($manager->is($member)) {
                throw InvalidReportingLine::selfReferential($member);
            }

            if (! $manager->belongsToOrganization($organization)) {
                throw InvalidReportingLine::notAMember($manager, $organization);
            }

            if (self::wouldCloseALoop($organization, $member, $manager)) {
                throw InvalidReportingLine::circular($member, $manager);
            }
        }

        $organization->users()->updateExistingPivot($member->getKey(), [
            'manager_id' => $manager?->getKey(),
        ]);
    }

    /**
     * Whether making the manager report-to of the member would close a loop.
     *
     * Walks up from the proposed manager: if the member is already somewhere
     * above them, the new link would join the two ends of the same chain. The
     * visited set guards the walk itself, so a loop that somehow already exists
     * in the data cannot hang this.
     */
    private static function wouldCloseALoop(Organization $organization, User $member, User $manager): bool
    {
        $seen = [];
        $current = $manager;

        while ($current instanceof User) {
            if ($current->is($member)) {
                return true;
            }

            if (isset($seen[$current->getKey()])) {
                return true;
            }

            $seen[$current->getKey()] = true;
            $current = $current->managerIn($organization);
        }

        return false;
    }
}
