<?php

use App\Modules\Core\Enums\OrganizationRole;
use App\Modules\Core\Enums\SuperPermission;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Support\OrganizationContext;

/*
|--------------------------------------------------------------------------
| Organization authorization
|--------------------------------------------------------------------------
|
| Covers the ground the `permission:` middleware could not: it could confirm
| an admin held `organizations.update`, but never that the organization in the
| URL was one the identity asking should reach.
|
*/

test('an admin without the view permission can not see an organization', function () {
    $admin = adminWith(SuperPermission::ViewRoles);
    $organization = Organization::factory()->create();

    $this->actingAs($admin, 'super')
        ->get(route('super.organizations.show', $organization))
        ->assertForbidden();
});

test('an admin without the update permission can not edit an organization', function () {
    $admin = adminWith(SuperPermission::ViewOrganizations);
    $organization = Organization::factory()->create();

    $this->actingAs($admin, 'super')
        ->put(route('super.organizations.update', $organization), ['name' => 'renamed'])
        ->assertForbidden();

    expect($organization->fresh()->name)->not->toBe('renamed');
});

test('an admin without the delete permission can not delete an organization', function () {
    $admin = adminWith(SuperPermission::ViewOrganizations, SuperPermission::UpdateOrganizations);
    $organization = Organization::factory()->create();

    $this->actingAs($admin, 'super')
        ->delete(route('super.organizations.destroy', $organization))
        ->assertForbidden();

    $this->assertDatabaseHas('organizations', ['id' => $organization->id]);
});

test('a company user can not reach the platform organization screens', function () {
    $organization = Organization::factory()->create();
    $user = memberOf($organization, OrganizationRole::Owner);

    // Signed in on the company platform, asking for the admin platform's own
    // index. The owner role grants `organization.view`, so this passes only if
    // the policy distinguishes the two guards rather than the permission names.
    $this->actingAs($user)
        ->get(route('super.organizations.index'))
        ->assertRedirect(route('super.login'));
});

test('a user can not switch to an organization they do not belong to', function () {
    [$mine, $theirs] = Organization::factory()->count(2)->create()->all();
    $user = memberOf($mine, OrganizationRole::Owner);

    $this->actingAs($user)
        ->withSession([OrganizationContext::SESSION_KEY => $mine->id])
        ->put(route('organizations.switch', $theirs))
        ->assertForbidden();
});

test('a user can switch to an organization they belong to', function () {
    $first = Organization::factory()->create();
    $second = Organization::factory()->create();
    $user = memberOf($first, OrganizationRole::Owner);
    $user->organizations()->attach($second);

    $this->actingAs($user)
        ->withSession([OrganizationContext::SESSION_KEY => $first->id])
        ->put(route('organizations.switch', $second))
        ->assertRedirect();

    expect(session(OrganizationContext::SESSION_KEY))->toBe($second->id);
});

test('an admin can not switch organizations', function () {
    $admin = superAdmin();
    $organization = Organization::factory()->create();

    // A super admin passes every gate through the `Gate::before` bypass, so
    // this asserts the route is unreachable rather than merely unauthorized:
    // switching is a company platform concern and admins have no context.
    $this->actingAs($admin, 'super')
        ->put(route('organizations.switch', $organization))
        ->assertRedirect(route('login'));
});
