<?php

use App\Modules\Core\Enums\SuperPermission;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;

/*
|--------------------------------------------------------------------------
| User authorization
|--------------------------------------------------------------------------
*/

test('an admin without the create organizations permission can not search users', function () {
    $admin = adminWith(SuperPermission::ViewOrganizations);
    User::factory()->create(['email' => 'findme@example.com']);

    $this->actingAs($admin, 'super')
        ->getJson(route('super.users.search', ['q' => 'findme']))
        ->assertForbidden();
});

test('an admin with the create organizations permission can search users', function () {
    $admin = adminWith(SuperPermission::CreateOrganizations);
    User::factory()->create(['email' => 'findme@example.com']);

    $this->actingAs($admin, 'super')
        ->getJson(route('super.users.search', ['q' => 'findme']))
        ->assertOk()
        ->assertJsonPath('users.0.email', 'findme@example.com');
});

test('an admin without the manage roles permission can not change a member role', function () {
    $admin = adminWith(SuperPermission::ViewOrganizations);
    $organization = Organization::factory()->create();
    $member = memberOf($organization);

    $this->actingAs($admin, 'super')
        ->put(route('super.organizations.members.roles.update', [$organization, $member]), [
            'roles' => [],
        ])
        ->assertForbidden();
});

test('a user can not delete another account', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    // The route resolves the account from the session, never from the URL, so
    // the policy is what stands between a signed-in user and someone else's
    // account should that ever change.
    $this->actingAs($user)
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertRedirect('/');

    $this->assertDatabaseHas('users', ['id' => $other->id]);
    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

test('a platform admin can not reach the company profile settings', function () {
    $admin = superAdmin();

    // A super admin passes every gate, so this asserts the company platform
    // never authenticates them in the first place — the `auth:web` guard on
    // those routes is what makes the policies below it meaningful.
    $this->actingAs($admin, 'super')
        ->get(route('profile.edit'))
        ->assertRedirect(route('login'));
});
