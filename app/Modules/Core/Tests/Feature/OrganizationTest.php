<?php

use App\Modules\Core\Models\Admin;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('admins can view the organizations index', function () {
    $admin = Admin::factory()->create();
    Organization::factory()->count(2)->create();

    $response = $this->actingAs($admin, 'super')->get(route('super.organizations.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('super/organizations/index')
        ->has('organizations', 2)
    );
});

test('admins can view the create organization page', function () {
    $admin = Admin::factory()->create();

    $this->actingAs($admin, 'super')
        ->get(route('super.organizations.create'))
        ->assertOk();
});

test('admins can create an organization', function () {
    $admin = Admin::factory()->create();

    $response = $this->actingAs($admin, 'super')->post(route('super.organizations.store'), [
        'name' => 'Acme Inc',
    ]);

    $response->assertRedirect(route('super.organizations.index'));
    $this->assertDatabaseHas('organizations', ['name' => 'Acme Inc']);
});

test('creating an organization requires a name', function () {
    $admin = Admin::factory()->create();

    $this->actingAs($admin, 'super')
        ->from(route('super.organizations.create'))
        ->post(route('super.organizations.store'), ['name' => ''])
        ->assertRedirect(route('super.organizations.create'))
        ->assertSessionHasErrors('name');

    $this->assertDatabaseCount('organizations', 0);
});

test('admins can view an organization and its members', function () {
    $admin = Admin::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach(User::factory()->count(2)->create());

    $response = $this->actingAs($admin, 'super')->get(route('super.organizations.show', $organization));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('super/organizations/show')
        ->where('organization.id', $organization->id)
        ->has('users', 2)
    );
});

test('admins can view the edit organization page', function () {
    $admin = Admin::factory()->create();
    $organization = Organization::factory()->create();

    $this->actingAs($admin, 'super')
        ->get(route('super.organizations.edit', $organization))
        ->assertOk();
});

test('admins can update an organization', function () {
    $admin = Admin::factory()->create();
    $organization = Organization::factory()->create(['name' => 'Old name']);

    $response = $this->actingAs($admin, 'super')->put(route('super.organizations.update', $organization), [
        'name' => 'New name',
    ]);

    $response->assertRedirect(route('super.organizations.index'));
    $this->assertDatabaseHas('organizations', ['id' => $organization->id, 'name' => 'New name']);
});

test('admins can delete an organization', function () {
    $admin = Admin::factory()->create();
    $organization = Organization::factory()->create();

    $response = $this->actingAs($admin, 'super')->delete(route('super.organizations.destroy', $organization));

    $response->assertRedirect(route('super.organizations.index'));
    $this->assertDatabaseMissing('organizations', ['id' => $organization->id]);
});

test('guests can not access the organizations area', function () {
    $this->get(route('super.organizations.index'))
        ->assertRedirect(route('super.login'));
});

test('company users can not access the organizations area', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('super.organizations.index'))
        ->assertRedirect(route('super.login'));
});

test('an organization has a many-to-many relationship with users', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create();

    $organization->users()->attach($user);

    expect($organization->users()->pluck('users.id'))->toContain($user->id);
    $this->assertDatabaseHas('organization_user', [
        'organization_id' => $organization->id,
        'user_id' => $user->id,
    ]);
});
