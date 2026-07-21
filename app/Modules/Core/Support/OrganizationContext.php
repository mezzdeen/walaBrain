<?php

namespace App\Modules\Core\Support;

use App\Modules\Core\Http\Middleware\SetOrganizationContext;
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
     * Whether the process is deliberately acting across every organization.
     *
     * Two states are not enough once queries are filtered automatically. A null
     * organization would otherwise mean both "the admin platform, which is
     * meant to read every tenant" and "a company request that lost its tenant",
     * forcing a choice between breaking the former and leaking through the
     * latter.
     *
     * The process starts unscoped so console commands, seeders and migrations
     * keep working as they always have; only something that names a tenant —
     * {@see self::use()}, always called by the web middleware — narrows it.
     */
    private static bool $unscoped = true;

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
     *
     * Scoping is switched on even when the organization is null: a company
     * request that resolved no organization is confined to nothing, which is
     * the safe reading. Only {@see self::useGlobal()} opens the process up.
     */
    public static function use(?Organization $organization): void
    {
        self::$current = $organization;
        self::$unscoped = false;

        setPermissionsTeamId($organization?->getKey());
    }

    /**
     * Act across every organization, as the admin platform does.
     */
    public static function useGlobal(): void
    {
        self::$current = null;
        self::$unscoped = true;
    }

    /**
     * The organization the current request is acting on.
     */
    public static function current(): ?Organization
    {
        return self::$current;
    }

    /**
     * Whether queries should be confined to one organization.
     */
    public static function isScoped(): bool
    {
        return ! self::$unscoped;
    }

    /**
     * Run a callback across every organization, then restore the previous
     * confinement.
     *
     * The deliberate way out of the global scope, for the few lookups that
     * legitimately precede membership — an invitation held by a signed-out
     * visitor, for one.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public static function withoutScoping(callable $callback): mixed
    {
        $organization = self::$current;
        $unscoped = self::$unscoped;

        self::useGlobal();

        try {
            return $callback();
        } finally {
            self::$current = $organization;
            self::$unscoped = $unscoped;
        }
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
     * Run a callback as though the given organization were active, then restore
     * whatever was active before.
     *
     * The context is installed by HTTP middleware, so anything running outside
     * a request — a queued job, an artisan command, a listener on a queue — has
     * none. Without this, scoped reads there return nothing and scoped writes
     * throw. Mirrors {@see PermissionTeam::on()}, and moves the permission team
     * too so role checks inside the callback agree with the organization.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public static function for(?Organization $organization, callable $callback): mixed
    {
        $previous = self::$current;
        $unscoped = self::$unscoped;
        $team = getPermissionsTeamId();

        self::use($organization);

        try {
            return $callback();
        } finally {
            // Restored field by field rather than through use(): the caller may
            // have been unscoped, and use() would leave the process confined to
            // whatever it was handed back.
            self::$current = $previous;
            self::$unscoped = $unscoped;

            setPermissionsTeamId($team);
        }
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
        self::$unscoped = true;
    }
}
