<?php

use App\Modules\Core\Enums\OrganizationPermission;
use App\Modules\Core\Enums\OrganizationRole;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\OrganizationContext;
use App\Modules\Core\Support\OrganizationRoles;
use App\Modules\Core\Support\PermissionTeam;

test('the active organization is resolved from the session', function () {
    [$first, $second] = Organization::factory()->count(2)->create()->all();
    $user = User::factory()->create();
    $user->organizations()->attach([$first->id, $second->id]);

    $this->actingAs($user)
        ->withSession([OrganizationContext::SESSION_KEY => $second->id])
        ->get(route('dashboard'))
        ->assertOk();

    expect(getPermissionsTeamId())->toBe($second->id);
});

test('the first organization is used when the session is empty', function () {
    [$first] = Organization::factory()->count(2)->create()->all();
    $user = User::factory()->create();
    $user->organizations()->attach($first);

    $this->actingAs($user)->get(route('dashboard'))->assertOk();

    expect(getPermissionsTeamId())->toBe($first->id)
        ->and(session(OrganizationContext::SESSION_KEY))->toBe($first->id);
});

test('a session organization is discarded once membership is revoked', function () {
    [$first, $second] = Organization::factory()->count(2)->create()->all();
    $user = User::factory()->create();
    $user->organizations()->attach([$first->id, $second->id]);
    $user->organizations()->detach($second);

    $this->actingAs($user)
        ->withSession([OrganizationContext::SESSION_KEY => $second->id])
        ->get(route('dashboard'))
        ->assertOk();

    expect(getPermissionsTeamId())->toBe($first->id);
});

test('a user with no organizations has no permission team', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('organizations.none'))
        ->assertOk();

    expect(getPermissionsTeamId())->toBeNull();
});

test('a guest has no permission team', function () {
    $this->get('/')->assertOk();

    expect(getPermissionsTeamId())->toBeNull();
});

test('a user can switch to another of their organizations', function () {
    [$first, $second] = Organization::factory()->count(2)->create()->all();
    $user = User::factory()->create();
    $user->organizations()->attach([$first->id, $second->id]);

    // Switched from somewhere other than the dashboard, so the assertion below
    // proves the redirect rather than merely echoing where the request came
    // from — which is all it would prove if this were `back()`.
    $this->actingAs($user)
        ->withSession([OrganizationContext::SESSION_KEY => $first->id])
        ->from(route('profile.edit'))
        ->put(route('organizations.switch', $second))
        ->assertRedirect(route('dashboard'));

    expect(session(OrganizationContext::SESSION_KEY))->toBe($second->id);
});

test('a user can not switch to an organization they do not belong to', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('organizations.switch', $organization))
        ->assertForbidden();
});

test('guests can not switch organizations', function () {
    $organization = Organization::factory()->create();

    $this->put(route('organizations.switch', $organization))
        ->assertRedirect(route('login'));
});

test('permissions follow the organization the user switched to', function () {
    [$first, $second] = Organization::factory()->count(2)->create()->all();
    $user = memberOf($first, OrganizationRole::Owner);

    OrganizationRoles::provision($second);
    $user->organizations()->attach($second);
    OrganizationRoles::within($second, fn () => $user->assignRole(OrganizationRole::Member->value));

    $this->actingAs($user)
        ->withSession([OrganizationContext::SESSION_KEY => $first->id])
        ->get(route('dashboard'))
        ->assertOk();
    expect($user->fresh()->can(OrganizationPermission::UpdateOrganization->value))->toBeTrue();

    $this->actingAs($user)
        ->withSession([OrganizationContext::SESSION_KEY => $second->id])
        ->get(route('dashboard'))
        ->assertOk();
    expect($user->fresh()->can(OrganizationPermission::UpdateOrganization->value))->toBeFalse();
});

test('an admin request uses the super team and not a company organization', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create();
    $user->organizations()->attach($organization);
    $admin = superAdmin();

    // Signed in on both platforms at once, which a shared browser makes possible.
    $this->actingAs($user)
        ->actingAs($admin, 'super')
        ->withSession([OrganizationContext::SESSION_KEY => $organization->id])
        ->get(route('super.dashboard'))
        ->assertOk();

    expect(getPermissionsTeamId())->toBe(PermissionTeam::SUPER);
});
