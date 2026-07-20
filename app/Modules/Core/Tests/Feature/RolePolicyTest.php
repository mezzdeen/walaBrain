<?php

use App\Modules\Core\Enums\OrganizationRole;
use App\Modules\Core\Enums\SuperPermission;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Support\OrganizationContext;
use App\Modules\Core\Support\OrganizationRoles;

/*
|--------------------------------------------------------------------------
| Role authorization across the two platforms
|--------------------------------------------------------------------------
|
| The `roles` table is shared: an organization's roles and the platform's
| global roles are rows in it, told apart by `guard_name` and
| `organization_id`. These assert the two sets stay sealed off from each
| other, whichever platform is asking.
|
*/

test('an admin without the update permission can not edit a platform role', function () {
    $admin = adminWith(SuperPermission::ViewRoles);
    $role = Role::create(['name' => 'auditor', 'guard_name' => 'super', 'organization_id' => null]);

    $this->actingAs($admin, 'super')
        ->put(route('super.roles.update', $role), ['name' => 'renamed', 'permissions' => []])
        ->assertForbidden();

    expect($role->fresh()->name)->toBe('auditor');
});

test('an admin without the create permission can not open the create form', function () {
    $admin = adminWith(SuperPermission::ViewRoles);

    $this->actingAs($admin, 'super')
        ->get(route('super.roles.create'))
        ->assertForbidden();
});

test('a member without the view permission can not see the organization roles', function () {
    $organization = Organization::factory()->create();
    $member = memberOf($organization);

    // The member role grants `organization.view` and `members.view`, never
    // `roles.view`.
    $this->actingAs($member)
        ->withSession([OrganizationContext::SESSION_KEY => $organization->id])
        ->get(route('settings.roles.index'))
        ->assertForbidden();
});

test('an owner can not reach a platform role through the company screens', function () {
    $organization = Organization::factory()->create();
    $owner = memberOf($organization, OrganizationRole::Owner);
    seedPermissions();
    $platformRole = Role::query()->where('guard_name', 'super')->firstOrFail();

    $this->actingAs($owner)
        ->withSession([OrganizationContext::SESSION_KEY => $organization->id])
        ->put(route('settings.roles.update', $platformRole), ['name' => 'hijacked'])
        ->assertForbidden();

    expect($platformRole->fresh()->name)->not->toBe('hijacked');
});

test('an organization role is not found through the platform screens', function () {
    // 404 rather than 403 on purpose: the platform role screens do not disclose
    // that an organization's roles exist. Asserted with a super admin, who
    // passes every gate, so this pins the scope check rather than a permission.
    $admin = superAdmin();
    $organization = Organization::factory()->create();
    OrganizationRoles::provision($organization);
    $role = Role::query()->firstWhere('organization_id', $organization->id);

    $this->actingAs($admin, 'super')
        ->put(route('super.roles.update', $role), ['name' => 'renamed', 'permissions' => []])
        ->assertNotFound();

    expect($role->fresh()->name)->not->toBe('renamed');
});

test('a company user can not reach the platform role screens', function () {
    $organization = Organization::factory()->create();
    $owner = memberOf($organization, OrganizationRole::Owner);

    $this->actingAs($owner)
        ->get(route('super.roles.index'))
        ->assertRedirect(route('super.login'));
});
