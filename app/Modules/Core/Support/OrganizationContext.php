<?php

namespace App\Modules\Core\Support;

use App\Http\Middleware\SetOrganizationContext;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;

/**
 * The organization a company platform request is acting on.
 *
 * A user can belong to several organizations and hold different roles in each,
 * so every permission check needs to know which one is active. The choice lives
 * in the session and is applied to spatie's team context by
 * {@see SetOrganizationContext} on the way in.
 */
final class OrganizationContext
{
    public const SESSION_KEY = 'organization_id';

    /**
     * The organization resolved for the current request, if any.
     */
    private static ?Organization $current = null;

    /**
     * Pick the organization for the user: whatever the session says, provided
     * they are still a member, and otherwise their first one.
     *
     * Membership is re-checked on every request because it can be revoked
     * between two of them, and a stale session must not keep granting access.
     */
    public static function resolve(User $user): ?Organization
    {
        $remembered = session(self::SESSION_KEY);

        $organization = is_int($remembered) || is_string($remembered)
            ? $user->organizations()->whereKey($remembered)->first()
            : null;

        if ($organization === null) {
            session()->forget(self::SESSION_KEY);
            $organization = $user->organizations()->oldest('organizations.id')->first();
        }

        if ($organization !== null) {
            session()->put(self::SESSION_KEY, $organization->getKey());
        }

        return $organization;
    }

    /**
     * Apply the organization as the active permission team for this request.
     */
    public static function use(?Organization $organization): void
    {
        self::$current = $organization;

        setPermissionsTeamId($organization?->getKey());
    }

    /**
     * The organization the current request is acting on.
     */
    public static function current(): ?Organization
    {
        return self::$current;
    }

    /**
     * Switch the user to another of their organizations.
     *
     * The caller is responsible for having authorized the move; this only
     * records it.
     */
    public static function switch(Organization $organization): void
    {
        session()->put(self::SESSION_KEY, $organization->getKey());

        self::use($organization);
    }

    /**
     * Drop the resolved organization without touching the session or the
     * permission team.
     *
     * Needed because the resolved organization is per-process state, not
     * per-request state: a worker that serves a company request and then an
     * admin request would otherwise carry the first one's organization into the
     * second.
     */
    public static function clear(): void
    {
        self::$current = null;
    }
}
