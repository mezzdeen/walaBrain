<?php

use App\Modules\Core\Enums\OrganizationRole;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\OrganizationInvitation;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\User;
use App\Modules\Core\Notifications\OrganizationMemberInvitation;
use App\Modules\Core\Support\OrganizationContext;
use App\Modules\Core\Support\OrganizationInvitations;
use App\Modules\Core\Support\OrganizationRoles;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * A pending member invitation to the organization, with the plaintext token its
 * mail would carry handed back for the accept flow.
 *
 * @return array{0: OrganizationInvitation, 1: string}
 */
function memberInvitationFor(Organization $organization, string $email, string $role = 'member'): array
{
    $plainToken = Str::random(64);

    $invitation = OrganizationInvitation::factory()->create([
        'organization_id' => $organization->getKey(),
        'email' => $email,
        'role' => $role,
        'token' => OrganizationInvitations::hash($plainToken),
    ]);

    return [$invitation, $plainToken];
}

test('an owner can view the invitation screen', function () {
    $organization = Organization::factory()->create();
    $owner = memberOf($organization, OrganizationRole::Owner);

    $this->actingAs($owner)
        ->get(route('invitations.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('invitations/index'));
});

test('a member can not view the invitation screen', function () {
    $organization = Organization::factory()->create();
    $member = memberOf($organization, OrganizationRole::Member);

    $this->actingAs($member)
        ->get(route('invitations.index'))
        ->assertForbidden();
});

// Sent away rather than refused: with no organization there is nobody to
// invite anyone into, so the explanation is more use than a 403.
test('a user with no organization is sent away from the invitation screen', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('invitations.index'))
        ->assertRedirect(route('organizations.none'));
});

test('the screen follows the organization the user is acting on', function () {
    [$first, $second] = Organization::factory()->count(2)->create()->all();
    $user = memberOf($first, OrganizationRole::Owner);
    OrganizationRoles::provision($second);
    $user->organizations()->attach($second);
    OrganizationRoles::within($second, fn () => $user->assignRole(OrganizationRole::Member->value));

    $this->actingAs($user)
        ->withSession([OrganizationContext::SESSION_KEY => $first->id])
        ->get(route('invitations.index'))
        ->assertOk();

    $this->actingAs($user)
        ->withSession([OrganizationContext::SESSION_KEY => $second->id])
        ->get(route('invitations.index'))
        ->assertForbidden();
});

test('guests can not access the invitation screen', function () {
    $this->get(route('invitations.index'))->assertRedirect(route('login'));
});

test('an owner invites a member under a chosen role, pending until accepted', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $owner = memberOf($organization, OrganizationRole::Owner);

    $this->actingAs($owner)
        ->post(route('invitations.manage.store'), [
            'email' => 'newcomer@example.com',
            'role' => OrganizationRole::Member->value,
        ])
        ->assertRedirect(route('invitations.index'));

    $invitation = OrganizationInvitation::query()->firstWhere('email', 'newcomer@example.com');

    expect($invitation)->not->toBeNull()
        ->and($invitation->role)->toBe(OrganizationRole::Member->value)
        ->and($invitation->invited_by_user_id)->toBe($owner->getKey())
        ->and($invitation->isPending())->toBeTrue();

    Notification::assertSentOnDemand(OrganizationMemberInvitation::class);
});

test('an organization can not invite someone as its owner', function () {
    $organization = Organization::factory()->create();
    $owner = memberOf($organization, OrganizationRole::Owner);

    $this->actingAs($owner)
        ->from(route('invitations.index'))
        ->post(route('invitations.manage.store'), [
            'email' => 'newcomer@example.com',
            'role' => OrganizationRole::Owner->value,
        ])
        ->assertSessionHasErrors('role');

    expect(OrganizationInvitation::query()->where('email', 'newcomer@example.com')->exists())->toBeFalse();
});

test('a role another organization defined can not be assigned', function () {
    $organization = Organization::factory()->create();
    $owner = memberOf($organization, OrganizationRole::Owner);

    // A custom role that exists only in another organization: its name is not
    // one this organization holds, so it must not be assignable here.
    $other = Organization::factory()->create();
    OrganizationRoles::provision($other);
    Role::create([
        'name' => 'auditor',
        'guard_name' => 'web',
        'organization_id' => $other->getKey(),
    ]);

    $this->actingAs($owner)
        ->from(route('invitations.index'))
        ->post(route('invitations.manage.store'), [
            'email' => 'newcomer@example.com',
            'role' => 'auditor',
        ])
        ->assertSessionHasErrors('role');
});

test('someone already in the organization can not be invited', function () {
    $organization = Organization::factory()->create();
    $owner = memberOf($organization, OrganizationRole::Owner);
    $existing = memberOf($organization, OrganizationRole::Member);

    $this->actingAs($owner)
        ->from(route('invitations.index'))
        ->post(route('invitations.manage.store'), [
            'email' => $existing->email,
            'role' => OrganizationRole::Member->value,
        ])
        ->assertSessionHasErrors('email');
});

test('an address can not hold two standing invitations to one organization', function () {
    $organization = Organization::factory()->create();
    $owner = memberOf($organization, OrganizationRole::Owner);
    memberInvitationFor($organization, 'newcomer@example.com');

    $this->actingAs($owner)
        ->from(route('invitations.index'))
        ->post(route('invitations.manage.store'), [
            'email' => 'newcomer@example.com',
            'role' => OrganizationRole::Member->value,
        ])
        ->assertSessionHasErrors('email');
});

test('a member without the invite permission can not issue one', function () {
    $organization = Organization::factory()->create();
    $member = memberOf($organization, OrganizationRole::Member);

    $this->actingAs($member)
        ->post(route('invitations.manage.store'), [
            'email' => 'newcomer@example.com',
            'role' => OrganizationRole::Member->value,
        ])
        ->assertForbidden();
});

test('an existing user only sees the organization once they accept', function () {
    $organization = Organization::factory()->create();
    OrganizationRoles::provision($organization);

    $invitee = User::factory()->create(['email' => 'invitee@example.com']);
    [$invitation, $plainToken] = memberInvitationFor($organization, 'invitee@example.com');

    expect($invitee->belongsToOrganization($organization))->toBeFalse();

    $this->actingAs($invitee)
        ->get(route('invitations.show', ['token' => $plainToken]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('invitations/accept')
            ->where('role', OrganizationRole::Member->value)
        );

    $this->actingAs($invitee)
        ->post(route('invitations.accept', ['token' => $plainToken]))
        ->assertRedirect(route('dashboard'));

    expect($invitee->fresh()->belongsToOrganization($organization))->toBeTrue()
        ->and($invitation->fresh()->accepted_at)->not->toBeNull();

    OrganizationRoles::within($organization, function () use ($invitee): void {
        expect($invitee->fresh()->hasRole(OrganizationRole::Member->value))->toBeTrue();
    });
});

test('one account can not accept an invitation addressed to another', function () {
    $organization = Organization::factory()->create();
    OrganizationRoles::provision($organization);
    [, $plainToken] = memberInvitationFor($organization, 'invitee@example.com');

    $intruder = User::factory()->create(['email' => 'someone-else@example.com']);

    $this->actingAs($intruder)
        ->post(route('invitations.accept', ['token' => $plainToken]))
        ->assertRedirect(route('invitations.show', ['token' => $plainToken]));

    expect($intruder->fresh()->belongsToOrganization($organization))->toBeFalse();
});

test('an owner withdraws a pending invitation', function () {
    $organization = Organization::factory()->create();
    $owner = memberOf($organization, OrganizationRole::Owner);
    [$invitation] = memberInvitationFor($organization, 'newcomer@example.com');

    $this->actingAs($owner)
        ->delete(route('invitations.manage.destroy', ['invitation' => $invitation->hash_id]))
        ->assertRedirect(route('invitations.index'));

    $this->assertModelMissing($invitation);
});

test('an invitation belonging to another organization can not be withdrawn', function () {
    $organization = Organization::factory()->create();
    $owner = memberOf($organization, OrganizationRole::Owner);

    $other = Organization::factory()->create();
    [$foreign] = memberInvitationFor($other, 'elsewhere@example.com');

    $this->actingAs($owner)
        ->delete(route('invitations.manage.destroy', ['invitation' => $foreign->hash_id]))
        ->assertNotFound();

    $this->assertModelExists($foreign);
});

test('resending mints a fresh link and window', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $owner = memberOf($organization, OrganizationRole::Owner);
    [$invitation, $plainToken] = memberInvitationFor($organization, 'newcomer@example.com');
    $originalToken = $invitation->token;

    $this->actingAs($owner)
        ->post(route('invitations.manage.resend', ['invitation' => $invitation->hash_id]))
        ->assertRedirect(route('invitations.index'));

    // The old plaintext link no longer resolves; a new hash has taken its place.
    expect($invitation->fresh()->token)->not->toBe($originalToken)
        ->and(OrganizationInvitations::findPendingByToken($plainToken))->toBeNull();

    Notification::assertSentOnDemand(OrganizationMemberInvitation::class);
});

test('member search suggests existing accounts, members included', function () {
    $organization = Organization::factory()->create();
    $owner = memberOf($organization, OrganizationRole::Owner);

    User::factory()->create(['email' => 'outsider@example.com']);

    $this->actingAs($owner)
        ->getJson(route('members.search', ['q' => 'outsider']))
        ->assertOk()
        ->assertJsonPath('users.0.email', 'outsider@example.com')
        ->assertJsonPath('users.0.already_member', false);

    // An existing account is surfaced even when it already belongs to the
    // organization, flagged so the field can mark it and refuse to pick it.
    $this->actingAs($owner)
        ->getJson(route('members.search', ['q' => $owner->email]))
        ->assertOk()
        ->assertJsonPath('users.0.email', $owner->email)
        ->assertJsonPath('users.0.already_member', true);
});

test('member search is closed to those without the invite permission', function () {
    $organization = Organization::factory()->create();
    $member = memberOf($organization, OrganizationRole::Member);

    $this->actingAs($member)
        ->getJson(route('members.search', ['q' => 'anyone']))
        ->assertForbidden();
});
