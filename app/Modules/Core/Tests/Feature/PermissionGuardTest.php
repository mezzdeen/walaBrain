<?php

use App\Modules\Core\Models\Admin;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\PermissionTeam;
use Illuminate\Database\QueryException;
use Spatie\Permission\Exceptions\GuardDoesNotMatch;
use Spatie\Permission\Exceptions\RoleDoesNotExist;

/**
 * Pins the two behaviours that fail silently rather than loudly: the sentinel
 * team that super admins are assigned on, and the guard a role defaults to.
 */
beforeEach(function () {
    setPermissionsTeamId(PermissionTeam::SUPER);
});

test('an admin can hold a global role on the sentinel team', function () {
    $admin = Admin::factory()->create();
    $role = Role::create(['name' => 'support', 'guard_name' => 'super', 'organization_id' => null]);

    $admin->assignRole($role);

    expect($admin->hasRole('support'))->toBeTrue();
    $this->assertDatabaseHas('model_has_roles', [
        'role_id' => $role->id,
        'model_type' => 'admin',
        'model_id' => $admin->id,
        'organization_id' => PermissionTeam::SUPER,
    ]);
});

test('a global role grants its permissions to an admin', function () {
    $admin = Admin::factory()->create();
    $permission = Permission::create(['name' => 'organizations.view', 'guard_name' => 'super']);
    Role::create(['name' => 'support', 'guard_name' => 'super', 'organization_id' => null])
        ->givePermissionTo($permission);

    $admin->assignRole('support');

    expect($admin->hasPermissionTo('organizations.view'))->toBeTrue()
        ->and($admin->can('organizations.view'))->toBeTrue();
});

test('a role created without an explicit guard name defaults to web', function () {
    $role = Role::create(['name' => 'ambiguous', 'organization_id' => null]);

    // Documents the trap: the Role model belongs to no auth provider, so spatie
    // falls back to config('auth.defaults.guard'). A super role created this way
    // would never match an admin, and nothing would report the mistake. Every
    // super role must therefore pass its guard name explicitly.
    expect($role->guard_name)->toBe('web');
});

test('a role can not be created on the sentinel team', function () {
    // Spatie stamps new roles with the current team id, so forgetting an
    // explicit null while the super context is active would file a platform
    // role under an organization that does not exist. The foreign key on
    // roles.organization_id turns that into an immediate failure.
    expect(fn () => Role::create(['name' => 'support', 'guard_name' => 'super']))
        ->toThrow(QueryException::class);
});

test('an admin can not be assigned a web guard role', function () {
    $admin = Admin::factory()->create();
    $role = Role::create(['name' => 'owner', 'guard_name' => 'web', 'organization_id' => null]);

    expect(fn () => $admin->assignRole($role))->toThrow(GuardDoesNotMatch::class);
});

test('an admin can not be assigned a web guard role by name', function () {
    $admin = Admin::factory()->create();
    Role::create(['name' => 'owner', 'guard_name' => 'web', 'organization_id' => null]);

    // Lookup by name is scoped to the model's own guard, so the name resolves to
    // nothing long before the guard comparison happens.
    expect(fn () => $admin->assignRole('owner'))->toThrow(RoleDoesNotExist::class);
});

test('a user can not be assigned a super guard role', function () {
    $user = User::factory()->create();
    $role = Role::create(['name' => 'support', 'guard_name' => 'super', 'organization_id' => null]);

    expect(fn () => $user->assignRole($role))->toThrow(GuardDoesNotMatch::class);
});

test('an organization owns its roles and deleting it deletes them', function () {
    $organization = Organization::factory()->create();
    setPermissionsTeamId($organization->id);
    $role = Role::create([
        'name' => 'owner',
        'guard_name' => 'web',
        'organization_id' => $organization->id,
    ]);

    $organization->delete();

    $this->assertDatabaseMissing('roles', ['id' => $role->id]);
});

test('a role in one organization does not resolve in another', function () {
    [$first, $second] = Organization::factory()->count(2)->create()->all();
    $user = User::factory()->create();
    $user->organizations()->attach([$first->id, $second->id]);

    setPermissionsTeamId($first->id);
    Role::create(['name' => 'owner', 'guard_name' => 'web', 'organization_id' => $first->id]);
    $user->assignRole('owner');

    expect($user->hasRole('owner'))->toBeTrue();

    setPermissionsTeamId($second->id);
    $user->unsetRelation('roles')->unsetRelation('permissions');

    expect($user->hasRole('owner'))->toBeFalse();
});
