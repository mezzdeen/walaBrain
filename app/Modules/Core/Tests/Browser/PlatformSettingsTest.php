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
