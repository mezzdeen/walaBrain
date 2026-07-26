<?php

use App\Modules\Core\Enums\OrganizationRole;
use App\Modules\Core\Enums\SuperPermission;
use App\Modules\Core\Enums\SuperRole;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\OrganizationRoles;
use Inertia\Testing\AssertableInertia as Assert;

test('admins can view the roles index', function () {
    $admin = superAdmin();

    $this->actingAs($admin, 'super')
        ->get(route('super.roles.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('super/roles/index')
            ->has('roles', count(SuperRole::cases()))
        );
});

test('the roles index excludes roles owned by an organization', function () {
    $admin = superAdmin();
    OrganizationRoles::provision(Organization::factory()->create());

    $this->actingAs($admin, 'super')
        ->get(route('super.roles.index'))
        ->assertInertia(fn (Assert $page) => $page->has('roles', count(SuperRole::cases())));
});

test('admins can view the create role page', function () {
    $admin = superAdmin();

    $this->actingAs($admin, 'super')
        ->get(route('super.roles.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('super/roles/create')
            ->has('permissionGroups')
        );
});

test('admins can create a role', function () {
    $admin = superAdmin();

    $this->actingAs($admin, 'super')
        ->post(route('super.roles.store'), [
            'name' => 'auditor',
            'permissions' => [SuperPermission::ViewOrganizations->value],
        ])
        ->assertRedirect(route('super.roles.index'));

    $role = Role::query()->firstWhere('name', 'auditor');

    expect($role->guard_name)->toBe('super')
        ->and($role->organization_id)->toBeNull()
        ->and($role->permissions->pluck('name')->all())
        ->toBe([SuperPermission::ViewOrganizations->value]);
});

test('creating a role requires a name', function () {
    $admin = superAdmin();

    $this->actingAs($admin, 'super')
        ->from(route('super.roles.create'))
        ->post(route('super.roles.store'), ['name' => ''])
        ->assertRedirect(route('super.roles.create'))
        ->assertSessionHasErrors('name');
});

test('a role name must be unique among the platform roles', function () {
    $admin = superAdmin();

    $this->actingAs($admin, 'super')
        ->from(route('super.roles.create'))
        ->post(route('super.roles.store'), ['name' => SuperRole::Support->value])
        ->assertSessionHasErrors('name');
});

test('a role name may repeat one an organization already uses', function () {
    $admin = superAdmin();
    OrganizationRoles::provision(Organization::factory()->create());

    // Same name, different guard and owner — two unrelated rows.
    $this->actingAs($admin, 'super')
        ->post(route('super.roles.store'), ['name' => OrganizationRole::Owner->value])
        ->assertSessionHasNoErrors();
});

test('unknown permissions are rejected', function () {
    $admin = superAdmin();

    $this->actingAs($admin, 'super')
        ->from(route('super.roles.create'))
        ->post(route('super.roles.store'), [
            'name' => 'auditor',
            'permissions' => ['organizations.detonate'],
        ])
        ->assertSessionHasErrors('permissions.0');
});

test('admins can update a role', function () {
    $admin = superAdmin();
    $role = Role::create(['name' => 'auditor', 'guard_name' => 'super', 'organization_id' => null]);

    $this->actingAs($admin, 'super')
        ->put(route('super.roles.update', $role), [
            'name' => 'reviewer',
            'permissions' => [SuperPermission::ViewAdmins->value],
        ])
        ->assertRedirect(route('super.roles.index'));

    expect($role->fresh()->name)->toBe('reviewer')
        ->and($role->fresh()->permissions->pluck('name')->all())
        ->toBe([SuperPermission::ViewAdmins->value]);
});

test('admins can delete a role', function () {
    $admin = superAdmin();
    $role = Role::create(['name' => 'auditor', 'guard_name' => 'super', 'organization_id' => null]);

    $this->actingAs($admin, 'super')
        ->delete(route('super.roles.destroy', $role))
        ->assertRedirect(route('super.roles.index'));

    $this->assertDatabaseMissing('roles', ['id' => $role->id]);
});

test('the super admin role can not be deleted', function () {
    $admin = superAdmin();
    $role = Role::query()->firstWhere('name', SuperRole::SuperAdmin->value);

    $this->actingAs($admin, 'super')
        ->delete(route('super.roles.destroy', $role))
        ->assertForbidden();

    $this->assertDatabaseHas('roles', ['id' => $role->id]);
});

test('the super admin role can not be edited', function () {
    $admin = superAdmin();
    $role = Role::query()->firstWhere('name', SuperRole::SuperAdmin->value);

    $this->actingAs($admin, 'super')
        ->put(route('super.roles.update', $role), ['name' => 'weakened', 'permissions' => []])
        ->assertForbidden();

    expect($role->fresh()->name)->toBe(SuperRole::SuperAdmin->value);
});

test('an organization role can not be reached through the super platform', function () {
    $admin = superAdmin();
    $organization = Organization::factory()->create();
    OrganizationRoles::provision($organization);
    $role = Role::query()->firstWhere('organization_id', $organization->id);

    $this->actingAs($admin, 'super')
        ->get(route('super.roles.edit', $role))
        ->assertNotFound();

    $this->actingAs($admin, 'super')
        ->delete(route('super.roles.destroy', $role))
        ->assertNotFound();
});

test('an admin without the view permission can not see the roles', function () {
    $admin = adminWith(SuperPermission::ViewOrganizations);

    $this->actingAs($admin, 'super')
        ->get(route('super.roles.index'))
        ->assertForbidden();
});

test('an admin without the create permission can not create a role', function () {
    $admin = adminWith(SuperPermission::ViewRoles);

    $this->actingAs($admin, 'super')
        ->post(route('super.roles.store'), ['name' => 'auditor'])
        ->assertForbidden();

    $this->assertDatabaseMissing('roles', ['name' => 'auditor']);
});

test('an admin without the delete permission can not delete a role', function () {
    $admin = adminWith(SuperPermission::ViewRoles);
    $role = Role::create(['name' => 'auditor', 'guard_name' => 'super', 'organization_id' => null]);

    $this->actingAs($admin, 'super')
        ->delete(route('super.roles.destroy', $role))
        ->assertForbidden();

    $this->assertDatabaseHas('roles', ['id' => $role->id]);
});

test('guests can not access the roles area', function () {
    $this->get(route('super.roles.index'))->assertRedirect(route('super.login'));
});

test('company users can not access the roles area', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('super.roles.index'))
        ->assertRedirect(route('super.login'));
});
