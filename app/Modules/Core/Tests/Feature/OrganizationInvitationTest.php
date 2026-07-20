<?php

use App\Modules\Core\Enums\OrganizationRole;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\OrganizationInvitation;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\OrganizationInvitations;
use App\Modules\Core\Support\OrganizationRoles;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Issue an invitation and hand back the plaintext token the mail would carry.
 *
 * @return array{0: OrganizationInvitation, 1: string}
 */
function invitationFor(Organization $organization, string $email = 'stranger@example.com'): array
{
    $plainToken = Str::random(64);

    $invitation = OrganizationInvitation::factory()->create([
        'organization_id' => $organization->getKey(),
        'email' => $email,
        'token' => OrganizationInvitations::hash($plainToken),
    ]);

    return [$invitation, $plainToken];
}

test('a valid invitation link renders the sign up form', function () {
    $organization = Organization::factory()->create();
    [, $plainToken] = invitationFor($organization);

    $this->get(route('invitations.show', ['token' => $plainToken]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/accept-invitation')
            ->where('email', 'stranger@example.com')
            ->where('organization.name', $organization->name)
            ->where('token', $plainToken)
        );
});

test('an unknown token is refused', function () {
    $this->get(route('invitations.show', ['token' => Str::random(64)]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('reason', 'invalid'));
});

test('an expired token is refused', function () {
    $organization = Organization::factory()->create();
    $plainToken = Str::random(64);

    OrganizationInvitation::factory()->expired()->create([
        'organization_id' => $organization->getKey(),
        'token' => OrganizationInvitations::hash($plainToken),
    ]);

    $this->get(route('invitations.show', ['token' => $plainToken]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('reason', 'expired'));
});

test('an already accepted token is refused', function () {
    $organization = Organization::factory()->create();
    $plainToken = Str::random(64);

    OrganizationInvitation::factory()->accepted()->create([
        'organization_id' => $organization->getKey(),
        'token' => OrganizationInvitations::hash($plainToken),
    ]);

    $this->get(route('invitations.show', ['token' => $plainToken]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('reason', 'accepted'));
});

test('accepting an invitation creates the owner and signs them in', function () {
    $organization = Organization::factory()->create();
    OrganizationRoles::provision($organization);
    [$invitation, $plainToken] = invitationFor($organization);

    $response = $this->post(route('invitations.store', ['token' => $plainToken]), [
        'name' => 'New Owner',
        'password' => 'Str0ng-Password!',
        'password_confirmation' => 'Str0ng-Password!',
    ]);

    $response->assertRedirect(config('fortify.home'));
    $this->assertAuthenticated();

    $user = User::query()->firstWhere('email', 'stranger@example.com');

    expect($user->name)->toBe('New Owner')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->belongsToOrganization($organization))->toBeTrue();

    OrganizationRoles::within($organization, function () use ($user): void {
        expect($user->fresh()->hasRole(OrganizationRole::Owner->value))->toBeTrue();
    });

    expect($invitation->fresh()->accepted_at)->not->toBeNull();
});

test('an invitation can not be accepted twice', function () {
    $organization = Organization::factory()->create();
    OrganizationRoles::provision($organization);
    [, $plainToken] = invitationFor($organization);

    $payload = [
        'name' => 'New Owner',
        'password' => 'Str0ng-Password!',
        'password_confirmation' => 'Str0ng-Password!',
    ];

    $this->post(route('invitations.store', ['token' => $plainToken]), $payload);
    $this->post(route('logout'));

    $this->post(route('invitations.store', ['token' => $plainToken]), $payload)
        ->assertRedirect(route('invitations.show', ['token' => $plainToken]));

    expect(User::query()->where('email', 'stranger@example.com')->count())->toBe(1);
});

test('accepting requires a confirmed password', function () {
    $organization = Organization::factory()->create();
    [, $plainToken] = invitationFor($organization);

    $this->from(route('invitations.show', ['token' => $plainToken]))
        ->post(route('invitations.store', ['token' => $plainToken]), [
            'name' => 'New Owner',
            'password' => 'Str0ng-Password!',
            'password_confirmation' => 'something-else',
        ])
        ->assertSessionHasErrors('password');

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'stranger@example.com']);
});

test('an invitee whose address was registered in the meantime is sent to log in', function () {
    $organization = Organization::factory()->create();
    [, $plainToken] = invitationFor($organization);

    User::factory()->create(['email' => 'stranger@example.com']);

    $this->get(route('invitations.show', ['token' => $plainToken]))
        ->assertRedirect(route('login'));

    $this->post(route('invitations.store', ['token' => $plainToken]), [
        'name' => 'New Owner',
        'password' => 'Str0ng-Password!',
        'password_confirmation' => 'Str0ng-Password!',
    ])->assertRedirect(route('login'));

    expect(User::query()->where('email', 'stranger@example.com')->count())->toBe(1);
});

test('a signed in user is kept away from the invitation pages', function () {
    $organization = Organization::factory()->create();
    [, $plainToken] = invitationFor($organization);

    $this->actingAs(User::factory()->create())
        ->get(route('invitations.show', ['token' => $plainToken]))
        ->assertRedirect();
});

test('public registration is disabled', function () {
    Notification::fake();

    expect(fn () => route('register'))->toThrow(Exception::class);

    $this->get('/register')->assertNotFound();
});
