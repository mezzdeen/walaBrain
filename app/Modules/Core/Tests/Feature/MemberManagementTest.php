<?php

use App\Modules\Core\Enums\OrganizationRole;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\OrganizationRoles;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * A member of the organization holding the given role, created with the given
 * attributes so its name and email can be searched for.
 *
 * @param  array<string, mixed>  $attributes
 */
function organizationMember(Organization $organization, array $attributes = [], OrganizationRole $role = OrganizationRole::Member): User
{
    OrganizationRoles::provision($organization);

    $user = User::factory()->create($attributes);
    $user->organizations()->attach($organization);

    setPermissionsTeamId($organization->getKey());
    $user->assignRole($role->value);

    return $user;
}

test('an owner sees everyone in the organization', function () {
    $organization = Organization::factory()->create();
    $owner = organizationMember($organization, ['first_name' => 'Zoe', 'last_name' => 'Owner'], OrganizationRole::Owner);
    organizationMember($organization, ['first_name' => 'Alice', 'last_name' => 'Smith']);
    organizationMember($organization, ['first_name' => 'Bob', 'last_name' => 'Jones']);

    $this->actingAs($owner)
        ->get(route('members.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('members/index')
            ->has('members', 3)
            ->has('roles')
        );
});

test('a member without the manage permission is refused', function () {
    $organization = Organization::factory()->create();
    $member = organizationMember($organization);

    $this->actingAs($member)
        ->get(route('members.index'))
        ->assertForbidden();
});

// Sent away rather than refused: with no organization there is no membership to
// administer, so the explanation is more use than a 403.
test('a user with no organization is sent away', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('members.index'))
        ->assertRedirect(route('organizations.none'));
});

test('guests can not access the member screen', function () {
    $this->get(route('members.index'))->assertRedirect(route('login'));
});

test('the search narrows members by name', function () {
    $organization = Organization::factory()->create();
    $owner = organizationMember($organization, ['first_name' => 'Zoe', 'last_name' => 'Owner'], OrganizationRole::Owner);
    organizationMember($organization, ['first_name' => 'Alice', 'last_name' => 'Smith', 'email' => 'alice@example.com']);
    organizationMember($organization, ['first_name' => 'Bob', 'last_name' => 'Jones', 'email' => 'bob@example.com']);

    $this->actingAs($owner)
        ->get(route('members.index', ['search' => 'alice']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('members', 1)
            ->where('members.0.email', 'alice@example.com')
        );
});

test('the search narrows members by email', function () {
    $organization = Organization::factory()->create();
    $owner = organizationMember($organization, ['first_name' => 'Zoe', 'last_name' => 'Owner'], OrganizationRole::Owner);
    organizationMember($organization, ['first_name' => 'Alice', 'last_name' => 'Smith', 'email' => 'alice@example.com']);
    organizationMember($organization, ['first_name' => 'Bob', 'last_name' => 'Jones', 'email' => 'bob@example.com']);

    $this->actingAs($owner)
        ->get(route('members.index', ['search' => 'bob@']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('members', 1)
            ->where('members.0.email', 'bob@example.com')
        );
});

test('the role filter narrows members by role', function () {
    $organization = Organization::factory()->create();
    $owner = organizationMember($organization, ['first_name' => 'Zoe', 'last_name' => 'Owner'], OrganizationRole::Owner);
    organizationMember($organization, ['first_name' => 'Alice', 'last_name' => 'Smith']);

    $this->actingAs($owner)
        ->get(route('members.index', ['role' => OrganizationRole::Owner->value]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('members', 1)
            ->where('members.0.roles.0', OrganizationRole::Owner->value)
        );
});
