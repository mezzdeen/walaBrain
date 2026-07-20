<?php

namespace App\Modules\Core\Database\Seeders;

use App\Modules\Core\Enums\OrganizationPermission;
use App\Modules\Core\Enums\SuperPermission;
use App\Modules\Core\Enums\SuperRole;
use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Support\OrganizationRoles;
use App\Modules\Core\Support\PermissionTeam;
use Illuminate\Database\Seeder;
use Spatie\Permission\Contracts\Permission as PermissionContract;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Seed the permission catalogue for both guards and the global roles of the
     * super admin platform.
     *
     * Organization roles are not seeded here: every organization owns its own
     * copies, provisioned by {@see OrganizationRoles}
     * when the organization is created.
     *
     * Idempotent — safe to re-run against a seeded database.
     */
    public function run(): void
    {
        // A previous run in the same process may have memoised the old state.
        $this->forgetCachedPermissions();

        setPermissionsTeamId(PermissionTeam::SUPER);

        // Spatie normally invalidates its cache from model events, but
        // DatabaseSeeder mutes model events for itself and every seeder it
        // calls. Each stage therefore has to flush for the next one, or a stage
        // reads a snapshot taken before the previous one wrote and tries to
        // create rows that already exist.
        $this->seedPermissions();
        $this->forgetCachedPermissions();

        $this->seedSuperRoles();
        $this->forgetCachedPermissions();
    }

    /**
     * Create every permission for both guards.
     *
     * The guard name is always passed explicitly: spatie resolves the default
     * guard from the Permission model, which belongs to no auth provider, so an
     * omitted guard silently produces a `web` row.
     */
    private function seedPermissions(): void
    {
        foreach (SuperPermission::cases() as $permission) {
            Permission::findOrCreate($permission->value, 'super');
        }

        foreach (OrganizationPermission::cases() as $permission) {
            Permission::findOrCreate($permission->value, 'web');
        }
    }

    /**
     * Create the global super platform roles and sync their permissions.
     */
    private function seedSuperRoles(): void
    {
        foreach (SuperRole::cases() as $case) {
            // firstOrCreate rather than Role::create, which throws once the role
            // exists, and rather than Role::findOrCreate, which would stamp the
            // role with the current team instead of leaving it global.
            $role = Role::firstOrCreate([
                'name' => $case->value,
                'guard_name' => 'super',
                'organization_id' => null,
            ]);

            $role->syncPermissions(array_map(
                fn (SuperPermission $permission): PermissionContract => Permission::findOrCreate($permission->value, 'super'),
                $case->permissions(),
            ));
        }
    }

    private function forgetCachedPermissions(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
