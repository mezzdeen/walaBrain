<?php

use App\Modules\Core\Enums\OrganizationPermission;
use App\Modules\Core\Enums\OrganizationRole;
use App\Modules\Core\Enums\SuperPermission;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\OrganizationContext;
use Inertia\Testing\AssertableInertia as Assert;

test('an admin is given their permissions', function () {
    $admin = adminWith(SuperPermission::ViewOrganizations);

    $this->actingAs($admin, 'super')
        ->get(route('super.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('permissions', [SuperPermission::ViewOrganizations->value])
        );
});

test('a super admin is given every super permission', function () {
    $admin = superAdmin();

    $this->actingAs($admin, 'super')
        ->get(route('super.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('permissions', SuperPermission::values())
        );
});

test('a user is given the permissions of their active organization', function () {
    $organization = Organization::factory()->create();
    $user = memberOf($organization, OrganizationRole::Member);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('organization.id', $organization->id)
            ->where('organization.name', $organization->name)
            ->where('permissions', [
                OrganizationPermission::ViewOrganization->value,
                OrganizationPermission::ViewMembers->value,
            ])
        );
});

test('permissions change when the user switches organization', function () {
    [$first, $second] = Organization::factory()->count(2)->create()->all();
    $user = memberOf($first, OrganizationRole::Owner);
    $user->organizations()->attach($second);

    $this->actingAs($user)
        ->withSession([OrganizationContext::SESSION_KEY => $second->id])
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('organization.id', $second->id)
            // A member of the second organization, but holding no role there.
            ->where('permissions', [])
        );
});

test('a user is given the organizations they can switch to', function () {
    $organization = Organization::factory()->create(['name' => 'Acme']);
    $user = memberOf($organization, OrganizationRole::Owner);
    $other = Organization::factory()->create(['name' => 'Globex']);
    $user->organizations()->attach($other);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('organizations', 2)
            ->where('organizations.0.name', 'Acme')
            ->where('organizations.1.name', 'Globex')
        );
});

test('an admin is not given a company user organization', function () {
    $organization = Organization::factory()->create();
    $user = memberOf($organization, OrganizationRole::Owner);
    $admin = superAdmin();

    // Serve a company request first, so anything held in per-process state is
    // populated before the admin request asks for it.
    $this->actingAs($user)->get(route('dashboard'))->assertOk();

    $this->actingAs($admin, 'super')
        ->get(route('super.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('organization', null)
            ->where('organizations', [])
        );
});

test('the organizations prop carries no pivot data', function () {
    $organization = Organization::factory()->create(['name' => 'Acme']);
    $user = memberOf($organization, OrganizationRole::Owner);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('organizations', [['id' => $organization->id, 'name' => 'Acme']])
        );
});

test('a user with no organization is given no permissions', function () {
    $user = User::factory()->create();

    // The notice page rather than the dashboard: the dashboard now bounces a
    // user with no organization, and this is the screen they land on.
    $this->actingAs($user)
        ->get(route('organizations.none'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('permissions', [])
            ->where('organization', null)
            ->where('organizations', [])
        );
});
