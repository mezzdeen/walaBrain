<?php

use App\Modules\Core\Enums\OrganizationRole;
use App\Modules\Core\Enums\SuperPermission;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\OrganizationRoles;
use Inertia\Testing\AssertableInertia as Assert;

test('admins can view the roles an organization owns', function () {
    $admin = superAdmin();
    $organization = Organization::factory()->create();
    $member = memberOf($organization, OrganizationRole::Owner);

    $this->actingAs($admin, 'super')
        ->get(route('super.organizations.roles.index', $organization))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('super/organizations/roles')
            ->where('organization.id', $organization->id)
            ->has('roles', count(OrganizationRole::cases()))
            ->has('members', 1)
            ->where('members.0.id', $member->id)
            ->where('members.0.roles', [OrganizationRole::Owner->value])
        );
});

test('the screen only lists the roles of the organization being viewed', function () {
    $admin = superAdmin();
    [$first, $second] = Organization::factory()->count(2)->create()->all();
    OrganizationRoles::provision($first);
    OrganizationRoles::provision($second);

    $this->actingAs($admin, 'super')
        ->get(route('super.organizations.roles.index', $first))
        ->assertInertia(fn (Assert $page) => $page->has('roles', count(OrganizationRole::cases())));
});

test('admins can assign an organization role to a member', function () {
    $admin = superAdmin();
    $organization = Organization::factory()->create();
    $member = memberOf($organization, OrganizationRole::Member);

    $this->actingAs($admin, 'super')
        ->from(route('super.organizations.roles.index', $organization))
        ->put(route('super.organizations.members.roles.update', [$organization, $member]), [
            'roles' => [OrganizationRole::Owner->value],
        ])
        ->assertRedirect(route('super.organizations.roles.index', $organization));

    OrganizationRoles::within($organization, function () use ($member): void {
        expect($member->fresh()->hasRole(OrganizationRole::Owner->value))->toBeTrue()
            ->and($member->fresh()->hasRole(OrganizationRole::Member->value))->toBeFalse();
    });
});

test('assigning a role leaves the other organizations untouched', function () {
    $admin = superAdmin();
    [$first, $second] = Organization::factory()->count(2)->create()->all();
    $member = memberOf($first, OrganizationRole::Owner);
    OrganizationRoles::provision($second);
    $member->organizations()->attach($second);

    $this->actingAs($admin, 'super')
        ->put(route('super.organizations.members.roles.update', [$second, $member]), [
            'roles' => [OrganizationRole::Member->value],
        ]);

    OrganizationRoles::within($first, function () use ($member): void {
        expect($member->fresh()->hasRole(OrganizationRole::Owner->value))->toBeTrue();
    });
});

test('a role belonging to another organization can not be assigned', function () {
    $admin = superAdmin();
    [$first, $second] = Organization::factory()->count(2)->create()->all();
    $member = memberOf($first, OrganizationRole::Member);
    OrganizationRoles::provision($second);

    // The name exists in both, but the rule resolves it against this
    // organization's own rows, so a name it does not own is refused.
    $this->actingAs($admin, 'super')
        ->from(route('super.organizations.roles.index', $first))
        ->put(route('super.organizations.members.roles.update', [$first, $member]), [
            'roles' => ['auditor'],
        ])
        ->assertSessionHasErrors('roles.0');
});

test('roles can not be assigned to someone who is not a member', function () {
    $admin = superAdmin();
    $organization = Organization::factory()->create();
    OrganizationRoles::provision($organization);
    $stranger = User::factory()->create();

    $this->actingAs($admin, 'super')
        ->put(route('super.organizations.members.roles.update', [$organization, $stranger]), [
            'roles' => [OrganizationRole::Owner->value],
        ])
        ->assertNotFound();
});

test('an admin without the manage permission can not reach the screen', function () {
    $admin = adminWith(SuperPermission::ViewOrganizations);
    $organization = Organization::factory()->create();

    $this->actingAs($admin, 'super')
        ->get(route('super.organizations.roles.index', $organization))
        ->assertForbidden();
});

test('company users can not reach the organization roles screen', function () {
    $organization = Organization::factory()->create();
    $user = memberOf($organization, OrganizationRole::Owner);

    $this->actingAs($user)
        ->get(route('super.organizations.roles.index', $organization))
        ->assertRedirect(route('super.login'));
});
