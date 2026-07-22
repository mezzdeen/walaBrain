<?php

use App\Modules\Core\Enums\SuperPermission;
use App\Modules\Core\Support\PlatformSettings;

/*
|--------------------------------------------------------------------------
| Platform Settings Browser Tests
|--------------------------------------------------------------------------
|
| The registration switch is a pair of native radios styled as cards, so what
| the form actually submits depends on the compiled markup rather than on
| anything a feature test can see. These drive it in a real browser.
|
*/

test('picking a card and saving opens the platform', function () {
    $this->actingAs(adminWith(SuperPermission::ManagePlatformSettings), 'super');

    expect(PlatformSettings::registrationIsOpen())->toBeFalse();

    visit(route('super.settings.edit', absolute: false))
        ->assertSee('Currently closed')
        ->click('@registration-open')
        ->click('@save-platform-settings')
        ->assertSee('Currently open')
        ->assertNoJavaScriptErrors();

    expect(PlatformSettings::registrationIsOpen())->toBeTrue();
});

test('closing the platform again is saved', function () {
    $this->actingAs(adminWith(SuperPermission::ManagePlatformSettings), 'super');

    PlatformSettings::update([PlatformSettings::RegistrationOpen => true]);

    visit(route('super.settings.edit', absolute: false))
        ->assertSee('Currently open')
        ->click('@registration-closed')
        ->click('@save-platform-settings')
        ->assertSee('Currently closed')
        ->assertNoJavaScriptErrors();

    expect(PlatformSettings::registrationIsOpen())->toBeFalse();
});

test('the provider checkboxes only appear once the open card is picked', function () {
    $this->actingAs(adminWith(SuperPermission::ManagePlatformSettings), 'super');

    $page = visit(route('super.settings.edit', absolute: false));

    $page->assertDontSee('Login providers')
        ->click('@registration-open')
        ->assertSee('Login providers')
        ->assertNoJavaScriptErrors();
});

test('the sign-up page is reachable from the login page once the platform is open', function () {
    PlatformSettings::update([PlatformSettings::RegistrationOpen => true]);

    visit(route('login', absolute: false))
        ->assertSee('Sign up')
        ->click('Sign up')
        ->assertSee('Create an account')
        ->assertNoJavaScriptErrors();
});

test('the login page offers no way to sign up while the platform is closed', function () {
    visit(route('login', absolute: false))
        ->assertDontSee('Sign up')
        ->assertNoJavaScriptErrors();
});

test('closing the platform does not switch its providers off', function () {
    $this->actingAs(adminWith(SuperPermission::ManagePlatformSettings), 'super');

    // Open, with a provider switched on — the state the reviewer's scenario
    // starts from.
    PlatformSettings::update([
        PlatformSettings::RegistrationOpen => true,
        PlatformSettings::SocialProviders => ['google' => true],
    ]);

    visit(route('super.settings.edit', absolute: false))
        ->assertSee('Currently open')
        ->click('@registration-closed')
        ->click('@save-platform-settings')
        ->assertSee('Currently closed')
        ->assertNoJavaScriptErrors();

    // The provider the admin never touched is still enabled, not silently
    // switched off by closing the platform.
    expect(PlatformSettings::registrationIsOpen())->toBeFalse();
    expect(PlatformSettings::socialProviders())->toBe(['google' => true]);
});

test('a provider edit survives flipping the registration card', function () {
    $this->actingAs(adminWith(SuperPermission::ManagePlatformSettings), 'super');

    PlatformSettings::update([
        PlatformSettings::RegistrationOpen => true,
        PlatformSettings::SocialProviders => ['google' => true],
    ]);

    // Uncheck the provider, flip the card closed then open again, then save.
    // The edit is held in state, so hiding and showing the section must not
    // reset it back to the stored value.
    visit(route('super.settings.edit', absolute: false))
        ->click('@provider-google')
        ->click('@registration-closed')
        ->click('@registration-open')
        ->click('@save-platform-settings')
        ->assertSee('Currently open')
        ->assertNoJavaScriptErrors();

    expect(PlatformSettings::socialProviders())->toBe(['google' => false]);
});
