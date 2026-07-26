<?php

namespace App\Modules\Core\Support;

/**
 * Team identifiers for spatie/laravel-permission, where a "team" is an
 * organization.
 */
final class PermissionTeam
{
    /**
     * The team identifier used for every super platform role assignment.
     *
     * Spatie's own migration declares the team column on `model_has_roles` and
     * `model_has_permissions` as NOT NULL and folds it into the composite
     * primary key, so a null team can never be written to those pivots — and
     * `assignRole()` always stamps the pivot with the current team. Super admins
     * are not scoped to any organization, so they need a team value that no
     * organization can ever hold. Identity columns start at 1, which makes 0
     * permanently free.
     *
     * The roles themselves stay global: `roles.organization_id` is null for
     * every super role. Spatie matches a role whose team is null in any team
     * context, so a super admin sitting on team 0 resolves them correctly.
     */
    public const SUPER = 0;

    /**
     * Run a callback against a given team, then restore the previous one.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public static function on(int|string|null $team, callable $callback): mixed
    {
        $previous = getPermissionsTeamId();

        if ($previous === $team) {
            return $callback();
        }

        setPermissionsTeamId($team);

        try {
            return $callback();
        } finally {
            setPermissionsTeamId($previous);
        }
    }
}
