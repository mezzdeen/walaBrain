<?php

use App\Modules\Core\Enums\OrganizationRole;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\OrganizationInvitation;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\OrganizationInvitations;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * A deleted account keeps its address: the row is still there, and the unique
 * index on `users.email` still counts it. These cover the paths that ask "does
 * anyone hold this address?", which an ordinary query answers with no.
 */
test('an invitation to a deleted account address can not be opened', function () {
    User::factory()->create(['email' => 'gone@example.com'])->delete();

    $organization = Organization::factory()->create();
    $plainToken = Str::random(64);

    OrganizationInvitation::create([
        'organization_id' => $organization->getKey(),
        'email' => 'gone@example.com',
        'role' => OrganizationRole::Owner,
        'token' => OrganizationInvitations::hash($plainToken),
        'expires_at' => now()->addDays(7),
    ]);

    // Without this the form renders, and the registration it submits dies on
    // the unique constraint the deleted row still holds.
    $this->get(route('invitations.show', ['token' => $plainToken]))
        ->assertRedirect(route('login'));

    $this->post(route('invitations.store', ['token' => $plainToken]), [
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ])->assertRedirect(route('login'));

    $this->assertDatabaseCount('users', 1);
});

test('an organization can not be created for a deleted account address', function () {
    Notification::fake();

    $admin = superAdmin();
    User::factory()->create(['email' => 'gone@example.com'])->delete();

    $this->actingAs($admin, 'super')
        ->from(route('super.organizations.create'))
        ->post(route('super.organizations.store'), [
            'name' => 'Acme',
            'owner_email' => 'gone@example.com',
        ])
        ->assertSessionHasErrors('owner_email');

    // No half-built organization, and no invitation nobody could accept.
    $this->assertDatabaseCount('organizations', 0);
    $this->assertDatabaseCount('organization_invitations', 0);
});

test('a live account address is still accepted as an owner', function () {
    Notification::fake();

    $admin = superAdmin();
    User::factory()->create(['email' => 'ada@example.com']);

    $this->actingAs($admin, 'super')
        ->post(route('super.organizations.store'), [
            'name' => 'Acme',
            'owner_email' => 'ada@example.com',
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('organizations', ['name' => 'Acme']);
});
