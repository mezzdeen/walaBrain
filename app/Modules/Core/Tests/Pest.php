<?php

use App\Modules\Core\Database\Seeders\RolesAndPermissionsSeeder;
use App\Modules\Core\Enums\OrganizationRole;
use App\Modules\Core\Enums\SuperPermission;
use App\Modules\Core\Enums\SuperRole;
use App\Modules\Core\Models\Admin;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\OrganizationContext;
use App\Modules\Core\Support\OrganizationRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Required from tests/Pest.php, which is the only such file Pest boots. The
| module binds its own directory rather than relying on the application to know
| the path, so that everything the module's tests need lives with the module.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    // The organization context and the permission team are process-wide
    // statics. A real request resets them on its way in, but a test that pokes
    // them directly — or whose request left them set — hands them to the next
    // test, so a test's outcome can otherwise depend on the one before it. Reset
    // to the pristine boot state before each, so the order tests run in is never
    // a hidden fixture.
    ->beforeEach(function (): void {
        OrganizationContext::useGlobal();
        setPermissionsTeamId(null);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    })
    ->in(__DIR__);

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| Helpers for building the identities the module's tests act as. Global, as
| Pest helpers are, so they read the same from any test file.
|
*/

/**
 * Seed the permission catalogue and the global super platform roles.
 */
function seedPermissions(): void
{
    app(RolesAndPermissionsSeeder::class)->run();
}

/**
 * An admin holding the super admin role, which bypasses every permission check.
 */
function superAdmin(): Admin
{
    seedPermissions();

    return tap(Admin::factory()->create(), fn (Admin $admin) => $admin->assignRole(SuperRole::SuperAdmin->value));
}

/**
 * An admin holding exactly the given permissions and nothing else, for asserting
 * that a route rejects the admins it should.
 */
function adminWith(SuperPermission ...$permissions): Admin
{
    seedPermissions();

    $role = Role::create([
        'name' => 'test-role-'.Str::random(8),
        'guard_name' => 'super',
        'organization_id' => null,
    ]);

    $role->syncPermissions(array_map(
        fn (SuperPermission $permission) => Permission::findOrCreate($permission->value, 'super'),
        $permissions,
    ));

    return tap(Admin::factory()->create(), fn (Admin $admin) => $admin->assignRole($role));
}

/**
 * A user who is a member of the given organization, holding one of its roles.
 *
 * Leaves the permission team pointing at the organization, matching what the
 * middleware would have done for a real request.
 */
function memberOf(Organization $organization, OrganizationRole $role = OrganizationRole::Member): User
{
    OrganizationRoles::provision($organization);

    $user = User::factory()->create();
    $user->organizations()->attach($organization);

    setPermissionsTeamId($organization->getKey());
    $user->assignRole($role->value);

    return $user;
}
