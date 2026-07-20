<?php

namespace App\Modules\Core\Support;

use App\Modules\Core\Enums\OrganizationPermission;
use App\Modules\Core\Enums\OrganizationRole;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Role;
use Spatie\Permission\Contracts\Permission as PermissionContract;
use Spatie\Permission\PermissionRegistrar;

/**
 * Gives an organization its own copies of the default roles.
 *
 * Roles are team owned, so every organization needs its own rows rather than
 * sharing a global set. That has to happen whenever an organization comes into
 * existence, which is why this is called explicitly from the places that create
 * one rather than from a model observer: `DatabaseSeeder` mutes model events for
 * itself and every seeder it calls, so an observer would be skipped during
 * seeding without a word.
 */
final class OrganizationRoles
{
    /**
     * Create the default roles for the organization, if they do not exist yet.
     *
     * Safe to call more than once. Permissions are resolved rather than assumed,
     * so this works without the catalogue seeder having run first.
     */
    public static function provision(Organization $organization): void
    {
        // Spatie invalidates its permission cache from model events, which
        // DatabaseSeeder mutes for every seeder it runs. Reading a snapshot from
        // before the catalogue was written would make findOrCreate try to insert
        // permissions that already exist, so start from a known state.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        self::within($organization, function () use ($organization): void {
            foreach (OrganizationRole::cases() as $case) {
                $role = Role::firstOrCreate([
                    'name' => $case->value,
                    'guard_name' => 'web',
                    'organization_id' => $organization->getKey(),
                ]);

                $role->syncPermissions(array_map(
                    fn (OrganizationPermission $permission): PermissionContract => Permission::findOrCreate($permission->value, 'web'),
                    $case->permissions(),
                ));
            }
        });
    }

    /**
     * Run a callback with the permission team pointed at the organization, then
     * restore whatever team was active before.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public static function within(Organization $organization, callable $callback): mixed
    {
        return PermissionTeam::on($organization->getKey(), $callback);
    }
}
