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
use App\Modules\Core\Support\OrganizationRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Browser', __DIR__.'/../app/Modules');

/*
|--------------------------------------------------------------------------
| Browser Testing
|--------------------------------------------------------------------------
|
| Browser tests boot the compiled front-end in a real browser, so they wait
| a little longer than the default to let Inertia hydrate and the full page
| reloads that follow a locale change settle.
|
*/

pest()->browser()->timeout(10000);

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
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

    return tap(Admin::factory()->create())->assignRole(SuperRole::SuperAdmin->value);
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
        fn (SuperPermission $permission): Permission => Permission::findOrCreate($permission->value, 'super'),
        $permissions,
    ));

    return tap(Admin::factory()->create())->assignRole($role);
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
