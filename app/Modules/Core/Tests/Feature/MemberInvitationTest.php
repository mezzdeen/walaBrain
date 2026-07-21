<?php

use App\Modules\Core\Enums\OrganizationRole;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\OrganizationContext;
use App\Modules\Core\Support\OrganizationRoles;
use Inertia\Testing\AssertableInertia as Assert;

test('an owner can view the invitation screen', function () {
    $organization = Organization::factory()->create();
    $owner = memberOf($organization, OrganizationRole::Owner);

    $this->actingAs($owner)
        ->get(route('invitations.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('invitations/index'));
});

test('a member can not view the invitation screen', function () {
    $organization = Organization::factory()->create();
    $member = memberOf($organization, OrganizationRole::Member);

    $this->actingAs($member)
        ->get(route('invitations.index'))
        ->assertForbidden();
});

// Sent away rather than refused: with no organization there is nobody to
// invite anyone into, so the explanation is more use than a 403.
test('a user with no organization is sent away from the invitation screen', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('invitations.index'))
        ->assertRedirect(route('organizations.none'));
});

test('the screen follows the organization the user is acting on', function () {
    [$first, $second] = Organization::factory()->count(2)->create()->all();
    $user = memberOf($first, OrganizationRole::Owner);
    OrganizationRoles::provision($second);
    $user->organizations()->attach($second);
    OrganizationRoles::within($second, fn () => $user->assignRole(OrganizationRole::Member->value));

    $this->actingAs($user)
        ->withSession([OrganizationContext::SESSION_KEY => $first->id])
        ->get(route('invitations.index'))
        ->assertOk();

    $this->actingAs($user)
        ->withSession([OrganizationContext::SESSION_KEY => $second->id])
        ->get(route('invitations.index'))
        ->assertForbidden();
});

test('guests can not access the invitation screen', function () {
    $this->get(route('invitations.index'))->assertRedirect(route('login'));
});
