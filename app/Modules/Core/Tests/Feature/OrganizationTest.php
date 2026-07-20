<?php

use App\Modules\Core\Enums\OrganizationRole;
use App\Modules\Core\Enums\SuperPermission;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;
use App\Modules\Core\Notifications\OrganizationOwnerInvitation;
use App\Modules\Core\Notifications\OrganizationOwnershipGranted;
use App\Modules\Core\Support\OrganizationInvitations;
use App\Modules\Core\Support\OrganizationRoles;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

test('admins can view the organizations index', function () {
    $admin = superAdmin();
    Organization::factory()->count(2)->create();

    $response = $this->actingAs($admin, 'super')->get(route('super.organizations.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('super/organizations/index')
        ->has('organizations', 2)
    );
});

test('admins can view the create organization page', function () {
    $admin = superAdmin();

    $this->actingAs($admin, 'super')
        ->get(route('super.organizations.create'))
        ->assertOk();
});

test('admins can create an organization', function () {
    $admin = superAdmin();

    $response = $this->actingAs($admin, 'super')->post(route('super.organizations.store'), [
        'name' => 'Acme Inc',
        'owner_email' => 'owner@example.com',
    ]);

    $response->assertRedirect(route('super.organizations.index'));
    $this->assertDatabaseHas('organizations', ['name' => 'Acme Inc']);
});

test('creating an organization requires a name', function () {
    $admin = superAdmin();

    $this->actingAs($admin, 'super')
        ->from(route('super.organizations.create'))
        ->post(route('super.organizations.store'), ['name' => ''])
        ->assertRedirect(route('super.organizations.create'))
        ->assertSessionHasErrors('name');

    $this->assertDatabaseCount('organizations', 0);
});

test('admins can view an organization and its members', function () {
    $admin = superAdmin();
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
    $admin = superAdmin();
    $organization = Organization::factory()->create();

    $this->actingAs($admin, 'super')
        ->get(route('super.organizations.edit', $organization))
        ->assertOk();
});

test('admins can update an organization', function () {
    $admin = superAdmin();
    $organization = Organization::factory()->create(['name' => 'Old name']);

    $response = $this->actingAs($admin, 'super')->put(route('super.organizations.update', $organization), [
        'name' => 'New name',
    ]);

    $response->assertRedirect(route('super.organizations.index'));
    $this->assertDatabaseHas('organizations', ['id' => $organization->id, 'name' => 'New name']);
});

test('admins can delete an organization', function () {
    $admin = superAdmin();
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

test('an admin without the view permission can not see the organizations', function () {
    $admin = adminWith(SuperPermission::ViewRoles);

    $this->actingAs($admin, 'super')
        ->get(route('super.organizations.index'))
        ->assertForbidden();
});

test('an admin without the create permission can not create an organization', function () {
    $admin = adminWith(SuperPermission::ViewOrganizations);

    $this->actingAs($admin, 'super')
        ->post(route('super.organizations.store'), ['name' => 'Acme Inc'])
        ->assertForbidden();

    $this->assertDatabaseCount('organizations', 0);
});

test('an admin without the delete permission can not delete an organization', function () {
    $admin = adminWith(SuperPermission::ViewOrganizations, SuperPermission::UpdateOrganizations);
    $organization = Organization::factory()->create();

    $this->actingAs($admin, 'super')
        ->delete(route('super.organizations.destroy', $organization))
        ->assertForbidden();

    $this->assertDatabaseHas('organizations', ['id' => $organization->id]);
});

test('an admin with only the view permission can browse organizations', function () {
    $admin = adminWith(SuperPermission::ViewOrganizations);
    Organization::factory()->create();

    $this->actingAs($admin, 'super')
        ->get(route('super.organizations.index'))
        ->assertOk();
});

test('creating an organization provisions its default roles', function () {
    $admin = superAdmin();

    $this->actingAs($admin, 'super')->post(route('super.organizations.store'), [
        'name' => 'Acme Inc',
        'owner_email' => 'owner@example.com',
    ]);

    $organization = Organization::query()->firstWhere('name', 'Acme Inc');

    foreach (OrganizationRole::cases() as $case) {
        $this->assertDatabaseHas('roles', [
            'name' => $case->value,
            'guard_name' => 'web',
            'organization_id' => $organization->id,
        ]);
    }
});

test('creating an organization requires an owner email', function () {
    $admin = superAdmin();

    $this->actingAs($admin, 'super')
        ->from(route('super.organizations.create'))
        ->post(route('super.organizations.store'), ['name' => 'Acme Inc'])
        ->assertRedirect(route('super.organizations.create'))
        ->assertSessionHasErrors('owner_email');

    $this->assertDatabaseCount('organizations', 0);
});

test('naming an existing user as owner attaches them straight away', function () {
    Notification::fake();

    $admin = superAdmin();
    $user = User::factory()->create(['email' => 'owner@example.com']);

    $this->actingAs($admin, 'super')->post(route('super.organizations.store'), [
        'name' => 'Acme Inc',
        'owner_email' => 'owner@example.com',
    ]);

    $organization = Organization::query()->firstWhere('name', 'Acme Inc');

    expect($user->belongsToOrganization($organization))->toBeTrue();

    OrganizationRoles::within($organization, function () use ($user): void {
        expect($user->fresh()->hasRole(OrganizationRole::Owner->value))->toBeTrue();
    });

    $this->assertDatabaseCount('organization_invitations', 0);
    Notification::assertSentTo($user, OrganizationOwnershipGranted::class);
});

test('the ownership emails are queued rather than sent during the request', function () {
    Queue::fake();

    $admin = superAdmin();
    User::factory()->create(['email' => 'owner@example.com']);

    $this->actingAs($admin, 'super')->post(route('super.organizations.store'), [
        'name' => 'Attached Inc',
        'owner_email' => 'owner@example.com',
    ]);

    $this->actingAs($admin, 'super')->post(route('super.organizations.store'), [
        'name' => 'Invited Inc',
        'owner_email' => 'stranger@example.com',
    ]);

    // Both branches hand the mail to the queue, so a slow or unreachable mail
    // server can never hold up creating an organization.
    Queue::assertPushed(SendQueuedNotifications::class, 2);
});

test('the queued emails wait for the transaction to commit', function () {
    Queue::fake();

    $admin = superAdmin();

    $this->actingAs($admin, 'super')->post(route('super.organizations.store'), [
        'name' => 'Invited Inc',
        'owner_email' => 'stranger@example.com',
    ]);

    // Without this a worker could pick the job up before the organization row
    // exists and mail a link to nothing.
    Queue::assertPushed(
        SendQueuedNotifications::class,
        fn (SendQueuedNotifications $job): bool => $job->afterCommit === true,
    );
});

test('the toast says which of the two things happened', function () {
    Notification::fake();

    $admin = superAdmin();
    User::factory()->create(['email' => 'owner@example.com']);

    $this->actingAs($admin, 'super')
        ->post(route('super.organizations.store'), [
            'name' => 'Attached Inc',
            'owner_email' => 'owner@example.com',
        ])
        ->assertSessionHas('inertia.flash_data', fn (array $flash): bool => $flash['toast']['message']
            === __('core::organizations.created_with_owner', ['email' => 'owner@example.com']));

    $this->actingAs($admin, 'super')
        ->post(route('super.organizations.store'), [
            'name' => 'Invited Inc',
            'owner_email' => 'stranger@example.com',
        ])
        ->assertSessionHas('inertia.flash_data', fn (array $flash): bool => $flash['toast']['message']
            === __('core::organizations.created_with_invitation', ['email' => 'stranger@example.com']));
});

test('the owner email is matched regardless of the case it is typed in', function () {
    Notification::fake();

    $admin = superAdmin();
    $user = User::factory()->create(['email' => 'owner@example.com']);

    $this->actingAs($admin, 'super')->post(route('super.organizations.store'), [
        'name' => 'Acme Inc',
        'owner_email' => 'Owner@Example.COM',
    ]);

    expect($user->fresh()->organizations)->toHaveCount(1);
    $this->assertDatabaseCount('organization_invitations', 0);
});

test('becoming the owner of an organization leaves the others untouched', function () {
    Notification::fake();

    $admin = superAdmin();
    $existing = Organization::factory()->create();
    $user = memberOf($existing, OrganizationRole::Member);

    $this->actingAs($admin, 'super')->post(route('super.organizations.store'), [
        'name' => 'Acme Inc',
        'owner_email' => $user->email,
    ]);

    $created = Organization::query()->firstWhere('name', 'Acme Inc');

    expect($user->fresh()->organizations->pluck('id'))
        ->toContain($existing->id)
        ->toContain($created->id);

    // The role they already held elsewhere has to survive being given a role here.
    OrganizationRoles::within($existing, function () use ($user): void {
        expect($user->fresh()->hasRole(OrganizationRole::Member->value))->toBeTrue();
    });

    OrganizationRoles::within($created, function () use ($user): void {
        expect($user->fresh()->hasRole(OrganizationRole::Owner->value))->toBeTrue();
    });
});

test('naming an unknown email as owner sends an invitation instead', function () {
    Notification::fake();

    $admin = superAdmin();

    $this->actingAs($admin, 'super')->post(route('super.organizations.store'), [
        'name' => 'Acme Inc',
        'owner_email' => 'stranger@example.com',
    ]);

    $organization = Organization::query()->firstWhere('name', 'Acme Inc');

    $this->assertDatabaseHas('organization_invitations', [
        'organization_id' => $organization->id,
        'email' => 'stranger@example.com',
        'role' => OrganizationRole::Owner->value,
        'accepted_at' => null,
    ]);

    // Nobody is created or attached until the invitation is accepted.
    $this->assertDatabaseMissing('users', ['email' => 'stranger@example.com']);
    expect($organization->users)->toHaveCount(0);

    Notification::assertSentOnDemand(OrganizationOwnerInvitation::class);
});

test('the invitation records which admin issued it', function () {
    Notification::fake();

    $admin = superAdmin();

    $this->actingAs($admin, 'super')->post(route('super.organizations.store'), [
        'name' => 'Acme Inc',
        'owner_email' => 'stranger@example.com',
    ]);

    $this->assertDatabaseHas('organization_invitations', [
        'email' => 'stranger@example.com',
        'invited_by_admin_id' => $admin->id,
    ]);
});

test('only the hash of the invitation token is stored', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $invitation = OrganizationInvitations::issue($organization, 'stranger@example.com');

    $sent = null;
    Notification::assertSentOnDemand(
        OrganizationOwnerInvitation::class,
        function (OrganizationOwnerInvitation $notification) use (&$sent): bool {
            $sent = $notification;

            return true;
        },
    );

    expect($invitation->token)
        ->not->toBe($sent->plainToken)
        ->toBe(hash('sha256', $sent->plainToken));
});

test('deleting an organization takes its pending invitations with it', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    OrganizationInvitations::issue($organization, 'stranger@example.com');

    $organization->delete();

    $this->assertDatabaseCount('organization_invitations', 0);
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
