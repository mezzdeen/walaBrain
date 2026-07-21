<?php

use App\Modules\Core\Models\User;

/*
|--------------------------------------------------------------------------
| Locale Browser Tests
|--------------------------------------------------------------------------
|
| The feature tests assert what the server sends. These assert what the user
| actually ends up looking at: the compiled React bundle running in a real
| browser, hydrated, with the document direction and dictionary in step.
|
*/

/**
 * The appearance settings page, the only screen that renders every locale
 * control at once: the header switcher and the inline language tabs.
 */
function appearancePage(): string
{
    return route('appearance.edit', absolute: false);
}

function dashboardPage(): string
{
    return route('dashboard', absolute: false);
}

test('the interface is rendered left to right in english by default', function () {
    $this->actingAs(User::factory()->create(['locale' => null]));

    visit(appearancePage())
        ->assertAttribute(':root', 'lang', 'en')
        ->assertAttribute(':root', 'dir', 'ltr')
        ->assertSee('Choose the language used across the interface')
        ->assertNoJavaScriptErrors();
});

test('a user who prefers arabic lands on a right to left interface', function () {
    $this->actingAs(User::factory()->create(['locale' => 'ar']));

    visit(appearancePage())
        ->assertAttribute(':root', 'lang', 'ar')
        ->assertAttribute(':root', 'dir', 'rtl')
        ->assertSee('اختر اللغة المستخدمة في جميع أنحاء الواجهة')
        ->assertNoJavaScriptErrors();
});

test('the header switcher flips the whole document to arabic', function () {
    $this->actingAs(User::factory()->create(['locale' => null]));

    visit(appearancePage())
        ->click('@language-switcher')
        ->click('@language-option-ar')
        // Switching reloads the document, and `dir` lands with the server HTML
        // while the text waits on React. Without settling first, the assertion
        // below races the re-render rather than testing it.
        ->waitForEvent('networkidle')
        ->assertAttribute(':root', 'lang', 'ar')
        ->assertAttribute(':root', 'dir', 'rtl')
        // The document and the rendered dictionary have to move together: a
        // reload that keeps Inertia's cached page leaves the two out of step.
        ->assertSee('اختر اللغة المستخدمة في جميع أنحاء الواجهة')
        ->assertNoJavaScriptErrors();
});

test('the appearance page tabs flip the whole document to arabic', function () {
    $this->actingAs(User::factory()->create(['locale' => 'en']));

    visit(appearancePage())
        ->click('@language-tab-ar')
        ->waitForEvent('networkidle')
        ->assertAttribute(':root', 'dir', 'rtl')
        ->assertSee('اختر اللغة المستخدمة في جميع أنحاء الواجهة')
        ->assertNoJavaScriptErrors();
});

test('the chosen language survives a later visit and is stored on the user', function () {
    $user = User::factory()->create(['locale' => null]);

    $this->actingAs($user);

    visit(appearancePage())
        ->click('@language-tab-ar')
        ->waitForEvent('networkidle')
        ->assertAttribute(':root', 'dir', 'rtl')
        ->navigate(appearancePage())
        ->waitForEvent('networkidle')
        ->assertAttribute(':root', 'dir', 'rtl')
        ->assertSee('اختر اللغة المستخدمة في جميع أنحاء الواجهة');

    expect($user->fresh()->locale)->toBe('ar');
});

test('the arabic dictionary reaches the client and labels the switcher', function () {
    $this->actingAs(User::factory()->create(['locale' => 'ar']));

    visit(dashboardPage())
        ->click('@language-switcher')
        ->assertSeeIn('@language-option-en', 'English')
        ->assertSeeIn('@language-option-ar', 'العربية')
        ->assertNoJavaScriptErrors();
});

test('the appearance switcher applies the dark theme', function () {
    $this->actingAs(User::factory()->create());

    visit(dashboardPage())
        ->click('@appearance-switcher')
        ->click('@appearance-option-dark')
        ->assertAttributeContains(':root', 'class', 'dark')
        ->assertNoJavaScriptErrors();
});

test('the signed in pages render without javascript errors in arabic', function () {
    $this->actingAs(User::factory()->create(['locale' => 'ar']));

    visit([
        dashboardPage(),
        appearancePage(),
        route('profile.edit', absolute: false),
        route('security.edit', absolute: false),
    ])->assertNoJavaScriptErrors();
});

test('the guest pages render without javascript errors', function () {
    visit([
        route('home', absolute: false),
        route('login', absolute: false),
        route('password.request', absolute: false),
    ])->assertNoJavaScriptErrors();
});

/*
|--------------------------------------------------------------------------
| Guest Switchers
|--------------------------------------------------------------------------
|
| A visitor decides how to read the interface before they ever sign in, so
| the same two controls the header carries are also within reach on the
| welcome screen and on every auth screen behind it.
|
*/

test('the welcome page switcher flips the whole document to arabic', function () {
    visit(route('home', absolute: false))
        ->click('@language-switcher')
        ->click('@language-option-ar')
        ->waitForEvent('networkidle')
        ->assertAttribute(':root', 'lang', 'ar')
        ->assertAttribute(':root', 'dir', 'rtl')
        ->assertNoJavaScriptErrors();
});

test('the welcome page switcher applies the dark theme', function () {
    visit(route('home', absolute: false))
        ->click('@appearance-switcher')
        ->click('@appearance-option-dark')
        ->assertAttributeContains(':root', 'class', 'dark')
        ->assertNoJavaScriptErrors();
});

test('the login page switcher flips the whole document to arabic', function () {
    visit(route('login', absolute: false))
        ->click('@language-switcher')
        ->click('@language-option-ar')
        ->waitForEvent('networkidle')
        ->assertAttribute(':root', 'dir', 'rtl')
        // The form itself has to move with the document, not just the shell.
        ->assertSee('تسجيل الدخول إلى حسابك')
        ->assertNoJavaScriptErrors();
});

test('the login page switcher applies the dark theme', function () {
    visit(route('login', absolute: false))
        ->click('@appearance-switcher')
        ->click('@appearance-option-dark')
        ->assertAttributeContains(':root', 'class', 'dark')
        ->assertNoJavaScriptErrors();
});
