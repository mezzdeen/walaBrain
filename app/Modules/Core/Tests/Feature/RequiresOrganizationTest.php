<?php

use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;

test('a user with no organization is sent to the notice', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertRedirect(route('organizations.none'));
});

test('a user with an organization reaches the dashboard', function () {
    $this->actingAs(memberOf(Organization::factory()->create()))
        ->get(route('dashboard'))
        ->assertOk();
});

test('the notice bounces a user who does have an organization', function () {
    $this->actingAs(memberOf(Organization::factory()->create()))
        ->get(route('organizations.none'))
        ->assertRedirect(route('dashboard'));
});

// Losing every organization must not lock someone out of their own account:
// these screens belong to the person, not to any organization.
test('a user with no organization can still reach their profile', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('profile.edit'))
        ->assertOk();
});

test('a user with no organization can still sign out', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('logout'))
        ->assertRedirect();

    $this->assertGuest();
});

test('a user who loses their last membership is sent to the notice next request', function () {
    $organization = Organization::factory()->create();
    $user = memberOf($organization);

    $this->actingAs($user)->get(route('dashboard'))->assertOk();

    $user->organizations()->detach($organization);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('organizations.none'));
});

test('a guest is sent to login rather than the notice', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});
