<?php

use App\Modules\Core\Models\Admin;
use App\Modules\Core\Models\User;

test('super login screen can be rendered', function () {
    $response = $this->get(route('super.login'));

    $response->assertOk();
});

test('admins can authenticate on the super guard', function () {
    $admin = Admin::factory()->create();

    $response = $this->post(route('super.login.store'), [
        'email' => $admin->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($admin, 'super');
    $response->assertRedirect(route('super.dashboard', absolute: false));
});

test('admins can not authenticate with an invalid password', function () {
    $admin = Admin::factory()->create();

    $this->post(route('super.login.store'), [
        'email' => $admin->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest('super');
});

test('authenticated admins can visit the super dashboard', function () {
    $admin = Admin::factory()->create();

    $response = $this->actingAs($admin, 'super')->get(route('super.dashboard'));

    $response->assertOk();
});

test('admins can logout of the super guard', function () {
    $admin = Admin::factory()->create();

    $response = $this->actingAs($admin, 'super')->post(route('super.logout'));

    $response->assertRedirect(route('super.login'));
    $this->assertGuest('super');
});

test('company users can not access the super platform', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('super.dashboard'));

    $response->assertRedirect(route('super.login'));
});

test('an admin session is isolated from the web guard', function () {
    $admin = Admin::factory()->create();

    $this->actingAs($admin, 'super');

    $this->assertAuthenticatedAs($admin, 'super');
    $this->assertGuest('web');
});
