<?php

use App\Modules\Core\Enums\OrganizationPermission;
use App\Modules\Core\Enums\OrganizationRole;
use App\Modules\Core\Enums\SuperPermission;
use App\Modules\Core\Enums\SuperRole;
use App\Modules\Core\Models\Admin;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Support\OrganizationRoles;
use App\Modules\Core\Support\PermissionTeam;

test('the seeder creates the catalogue for both guards', function () {
    seedPermissions();

    $this->assertDatabaseCount('permissions', count(SuperPermission::cases()) + count(OrganizationPermission::cases()));

    foreach (SuperPermission::cases() as $permission) {
        $this->assertDatabaseHas('permissions', ['name' => $permission->value, 'guard_name' => 'super']);
    }

    foreach (OrganizationPermission::cases() as $permission) {
        $this->assertDatabaseHas('permissions', ['name' => $permission->value, 'guard_name' => 'web']);
    }
});

test('the seeder creates global super roles', function () {
    seedPermissions();

    foreach (SuperRole::cases() as $case) {
        $this->assertDatabaseHas('roles', [
            'name' => $case->value,
            'guard_name' => 'super',
            'organization_id' => null,
        ]);
    }
});

test('the seeder is idempotent', function () {
    seedPermissions();
    $permissions = DB::table('permissions')->count();
    $roles = DB::table('roles')->count();

    seedPermissions();

    expect(DB::table('permissions')->count())->toBe($permissions)
        ->and(DB::table('roles')->count())->toBe($roles);
});

test('no web guard role is left without an organization', function () {
    seedPermissions();
    OrganizationRoles::provision(Organization::factory()->create());

    // A team-less web role would break spatie's uniqueness lookup, which treats
    // a null team as matching every team.
    expect(Role::query()->where('guard_name', 'web')->whereNull('organization_id')->exists())->toBeFalse();
});

test('provisioning gives an organization its own default roles', function () {
    $organization = Organization::factory()->create();

    OrganizationRoles::provision($organization);

    foreach (OrganizationRole::cases() as $case) {
        $this->assertDatabaseHas('roles', [
            'name' => $case->value,
            'guard_name' => 'web',
            'organization_id' => $organization->id,
        ]);
    }
});

test('each organization owns a separate copy of the default roles', function () {
    [$first, $second] = Organization::factory()->count(2)->create()->all();

    OrganizationRoles::provision($first);
    OrganizationRoles::provision($second);

    $owners = Role::query()->where('name', OrganizationRole::Owner->value)->pluck('organization_id', 'id');

    expect($owners)->toHaveCount(2)
        ->and($owners->values()->all())->toEqualCanonicalizing([$first->id, $second->id]);
});

test('provisioning is idempotent', function () {
    $organization = Organization::factory()->create();

    OrganizationRoles::provision($organization);
    OrganizationRoles::provision($organization);

    expect(Role::query()->where('organization_id', $organization->id)->count())
        ->toBe(count(OrganizationRole::cases()));
});

test('the owner role grants every organization permission', function () {
    $organization = Organization::factory()->create();
    $owner = memberOf($organization, OrganizationRole::Owner);

    expect($owner->can(OrganizationPermission::UpdateOrganization->value))->toBeTrue()
        ->and($owner->can(OrganizationPermission::RemoveMembers->value))->toBeTrue();
});

test('the member role only grants read access', function () {
    $organization = Organization::factory()->create();
    $member = memberOf($organization, OrganizationRole::Member);

    expect($member->can(OrganizationPermission::ViewMembers->value))->toBeTrue()
        ->and($member->can(OrganizationPermission::UpdateOrganization->value))->toBeFalse()
        ->and($member->can(OrganizationPermission::RemoveMembers->value))->toBeFalse();
});

test('a user holds different roles in different organizations', function () {
    [$first, $second] = Organization::factory()->count(2)->create()->all();
    $user = memberOf($first, OrganizationRole::Owner);

    OrganizationRoles::provision($second);
    $user->organizations()->attach($second);
    OrganizationRoles::within($second, fn () => $user->assignRole(OrganizationRole::Member->value));

    setPermissionsTeamId($first->id);
    $user->unsetRelation('roles')->unsetRelation('permissions');
    expect($user->can(OrganizationPermission::UpdateOrganization->value))->toBeTrue();

    setPermissionsTeamId($second->id);
    $user->unsetRelation('roles')->unsetRelation('permissions');
    expect($user->can(OrganizationPermission::UpdateOrganization->value))->toBeFalse()
        ->and($user->can(OrganizationPermission::ViewMembers->value))->toBeTrue();
});

test('the seeded super admin role grants every super permission', function () {
    $admin = superAdmin();

    setPermissionsTeamId(PermissionTeam::SUPER);

    foreach (SuperPermission::cases() as $permission) {
        expect($admin->can($permission->value))->toBeTrue();
    }
});

test('the support role only grants read access', function () {
    seedPermissions();
    setPermissionsTeamId(PermissionTeam::SUPER);
    $admin = Admin::factory()->create();
    $admin->assignRole(SuperRole::Support->value);

    expect($admin->can(SuperPermission::ViewOrganizations->value))->toBeTrue()
        ->and($admin->can(SuperPermission::DeleteOrganizations->value))->toBeFalse();
});
