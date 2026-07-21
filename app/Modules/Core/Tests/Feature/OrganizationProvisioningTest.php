<?php

use App\Modules\Core\Enums\OrganizationRole;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\PlatformSettings;
use Illuminate\Auth\Events\Verified;

test('confirming an address gives a new user an organization they own', function () {
    seedPermissions();

    $user = User::factory()->unverified()->create(['first_name' => 'Asmaa']);

    event(new Verified($user));

    $organization = Organization::query()->sole();

    expect($organization->name)->toBe(__('core::organizations.default_name', ['name' => 'Asmaa']))
        ->and($user->fresh()->belongsToOrganization($organization))->toBeTrue();

    // The roles have to exist for the organization to be administrable at all,
    // and the owner has to hold one of them.
    setPermissionsTeamId($organization->getKey());

    expect($user->fresh()->hasRole(OrganizationRole::Owner->value))->toBeTrue()
        ->and(Role::query()->where('organization_id', $organization->getKey())->pluck('name')->all())
        ->toContain(OrganizationRole::Owner->value, OrganizationRole::Member->value);
});

test('someone who already belongs to an organization is not given another', function () {
    seedPermissions();

    $organization = Organization::factory()->create();
    $user = memberOf($organization);

    event(new Verified($user));

    expect(Organization::query()->count())->toBe(1);
});

test('an invited user keeps the organization they were invited to', function () {
    seedPermissions();

    // The invite flow verifies on the spot and never fires `Verified`, so this
    // asserts the guard rather than the flow: even if it did fire, the
    // invitation's organization is not joined by a second one.
    $organization = Organization::factory()->create(['name' => 'Acme']);
    $user = memberOf($organization, OrganizationRole::Owner);

    event(new Verified($user));

    expect(Organization::query()->pluck('name')->all())->toBe(['Acme']);
});

test('signing up and confirming lands the user in their own organization', function () {
    seedPermissions();
    PlatformSettings::update([PlatformSettings::RegistrationOpen => true]);

    $this->post(route('register.store'), [
        'first_name' => 'Asmaa',
        'last_name' => 'Ezz',
        'email' => 'asmaa@example.com',
        'password' => 'correct-horse-battery-staple',
        'password_confirmation' => 'correct-horse-battery-staple',
    ]);

    $user = User::query()->sole();

    // Stands in for opening the mailed link, which is what the framework's own
    // verification controller turns into this event.
    $this->actingAs($user);
    event(new Verified($user));

    expect($user->fresh()->organizations()->count())->toBe(1);

    // The context is resolved per request from the user's memberships, so the
    // next one finds the organization without anything having set a session.
    $user->fresh()->forceFill(['email_verified_at' => now()])->save();

    $this->actingAs($user->fresh())->get(route('dashboard'))->assertOk();
});
