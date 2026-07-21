<?php

use App\Modules\Core\Enums\OrganizationRole;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\OrganizationContext;
use App\Modules\Core\Support\OrganizationRoles;
use Inertia\Testing\AssertableInertia as Assert;

test('an owner can view their organization settings', function () {
    $organization = Organization::factory()->create(['name' => 'Acme']);
    $owner = memberOf($organization, OrganizationRole::Owner);

    $this->actingAs($owner)
        ->get(route('organization.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('organization')
            ->where('organization.id', $organization->id)
            ->where('organization.name', 'Acme')
        );
});

test('an owner can update their organization', function () {
    $organization = Organization::factory()->create(['name' => 'Acme']);
    $owner = memberOf($organization, OrganizationRole::Owner);

    $this->actingAs($owner)
        ->patch(route('organization.update'), ['name' => 'Acme Holdings'])
        ->assertRedirect(route('organization.edit'));

    expect($organization->fresh()->name)->toBe('Acme Holdings');
});

test('the organization name is required', function () {
    $organization = Organization::factory()->create(['name' => 'Acme']);
    $owner = memberOf($organization, OrganizationRole::Owner);

    $this->actingAs($owner)
        ->from(route('organization.edit'))
        ->patch(route('organization.update'), ['name' => ''])
        ->assertSessionHasErrors('name');

    expect($organization->fresh()->name)->toBe('Acme');
});

// The whole point of the screen: it is the owner's, and a member holding
// `organization.view` must not reach an edit form they could not submit.
test('a member can not view or update the organization settings', function () {
    $organization = Organization::factory()->create(['name' => 'Acme']);
    $member = memberOf($organization, OrganizationRole::Member);

    $this->actingAs($member)
        ->get(route('organization.edit'))
        ->assertForbidden();

    $this->actingAs($member)
        ->patch(route('organization.update'), ['name' => 'Hijacked'])
        ->assertForbidden();

    expect($organization->fresh()->name)->toBe('Acme');
});

// Sent away rather than refused: with no organization there is nothing this
// screen could even be about, so the explanation is more use than a 403.
test('a user with no organization is sent away from the organization settings', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('organization.edit'))
        ->assertRedirect(route('organizations.none'));
});

test('the update only ever reaches the organization being acted on', function () {
    [$first, $second] = Organization::factory()->count(2)->create()->all();
    $user = memberOf($first, OrganizationRole::Owner);
    OrganizationRoles::provision($second);
    $user->organizations()->attach($second);
    OrganizationRoles::within($second, fn () => $user->assignRole(OrganizationRole::Member->value));

    // Owner in the first, member in the second: the same screen saves in one
    // and is refused in the other, and neither request names an organization.
    $this->actingAs($user)
        ->withSession([OrganizationContext::SESSION_KEY => $first->id])
        ->patch(route('organization.update'), ['name' => 'Renamed'])
        ->assertRedirect(route('organization.edit'));

    $this->actingAs($user)
        ->withSession([OrganizationContext::SESSION_KEY => $second->id])
        ->patch(route('organization.update'), ['name' => 'Hijacked'])
        ->assertForbidden();

    expect($first->fresh()->name)->toBe('Renamed')
        ->and($second->fresh()->name)->not->toBe('Hijacked');
});

test('guests can not access the organization settings', function () {
    $this->get(route('organization.edit'))->assertRedirect(route('login'));
});
