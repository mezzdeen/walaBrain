<?php

use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $this->actingAs(memberOf(Organization::factory()->create()));

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('a user with no organization is sent away from the dashboard', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard'))->assertRedirect(route('organizations.none'));
});
