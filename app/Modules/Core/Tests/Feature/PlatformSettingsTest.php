<?php

use App\Modules\Core\Enums\SuperPermission;
use App\Modules\Core\Models\PlatformSetting;
use App\Modules\Core\Support\PlatformSettings;
use Inertia\Testing\AssertableInertia as Assert;

test('the platform starts closed to sign-ups', function () {
    expect(PlatformSettings::registrationIsOpen())->toBeFalse();
});

test('an admin without the permission cannot reach the settings', function () {
    $admin = adminWith(SuperPermission::ViewOrganizations);

    $this->actingAs($admin, 'super')
        ->get(route('super.settings.edit'))
        ->assertForbidden();
});

test('an admin without the permission cannot change the settings', function () {
    $admin = adminWith(SuperPermission::ViewOrganizations);

    $this->actingAs($admin, 'super')
        ->patch(route('super.settings.update'), ['registration_open' => '1'])
        ->assertForbidden();

    expect(PlatformSettings::registrationIsOpen())->toBeFalse();
});

test('a guest is sent to the admin login', function () {
    $this->get(route('super.settings.edit'))
        ->assertRedirect(route('super.login'));
});

test('an admin with the permission sees the settings', function () {
    $admin = adminWith(SuperPermission::ManagePlatformSettings);

    $this->actingAs($admin, 'super')
        ->get(route('super.settings.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('super/settings')
            ->where('settings.registrationOpen', false)
            ->where('settings.socialProviders.google', false));
});

test('the screen reports the state the platform is actually in', function () {
    $admin = adminWith(SuperPermission::ManagePlatformSettings);

    PlatformSettings::update([PlatformSettings::RegistrationOpen => true]);

    // The screen distinguishes what is live from what is merely selected, so
    // this prop is what the badge reads and it has to follow the saved value.
    $this->actingAs($admin, 'super')
        ->get(route('super.settings.edit'))
        ->assertInertia(fn (Assert $page) => $page->where('settings.registrationOpen', true));
});

test('opening registration is stored and read back', function () {
    $admin = adminWith(SuperPermission::ManagePlatformSettings);

    $this->actingAs($admin, 'super')
        ->patch(route('super.settings.update'), [
            'registration_open' => '1',
            'social_providers' => ['google' => '1'],
        ])
        ->assertRedirect(route('super.settings.edit'));

    // Read through the accessor rather than the row, so the cache invalidation
    // is covered too: `all()` was already warmed by the authorization above.
    expect(PlatformSettings::registrationIsOpen())->toBeTrue()
        ->and(PlatformSettings::socialProviders())->toBe(['google' => true]);
});

test('an unchecked provider is stored as disabled rather than dropped', function () {
    $admin = adminWith(SuperPermission::ManagePlatformSettings);

    PlatformSettings::update([PlatformSettings::SocialProviders => ['google' => true]]);

    // A cleared checkbox posts nothing at all, which has to mean "off" rather
    // than "unchanged" or the box could never be unticked.
    $this->actingAs($admin, 'super')
        ->patch(route('super.settings.update'), ['registration_open' => '1'])
        ->assertRedirect(route('super.settings.edit'));

    expect(PlatformSettings::socialProviders())->toBe(['google' => false]);
});

test('a provider the application does not support cannot be stored', function () {
    $admin = adminWith(SuperPermission::ManagePlatformSettings);

    $this->actingAs($admin, 'super')
        ->patch(route('super.settings.update'), [
            'registration_open' => '1',
            'social_providers' => ['google' => '1', 'myspace' => '1'],
        ]);

    expect(PlatformSettings::socialProviders())->toBe(['google' => true])
        ->and(PlatformSetting::query()->find(PlatformSettings::SocialProviders)->value)
        ->not->toHaveKey('myspace');
});

test('registration_open is required', function () {
    $admin = adminWith(SuperPermission::ManagePlatformSettings);

    $this->actingAs($admin, 'super')
        ->from(route('super.settings.edit'))
        ->patch(route('super.settings.update'), [])
        ->assertSessionHasErrors('registration_open');
});

test('closing registration leaves the enabled providers untouched', function () {
    $admin = adminWith(SuperPermission::ManagePlatformSettings);

    // The starting state: open, with a provider switched on.
    PlatformSettings::update([
        PlatformSettings::RegistrationOpen => true,
        PlatformSettings::SocialProviders => ['google' => true],
    ]);

    // Closing the platform posts only the toggle — the screen hides the
    // provider section and posts nothing for it.
    $this->actingAs($admin, 'super')
        ->patch(route('super.settings.update'), ['registration_open' => '0'])
        ->assertRedirect(route('super.settings.edit'));

    // Closed, and the provider the admin never touched is still on rather than
    // silently switched off by the save.
    expect(PlatformSettings::registrationIsOpen())->toBeFalse()
        ->and(PlatformSettings::socialProviders())->toBe(['google' => true]);
});
