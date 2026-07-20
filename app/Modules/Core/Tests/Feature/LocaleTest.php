<?php

use App\Modules\Core\Models\Admin;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\Locale;
use App\Modules\Core\Support\Translations;
use Inertia\Testing\AssertableInertia;

test('the compiled dictionary carries module and framework messages', function () {
    $messages = Translations::for('ar');

    expect($messages['core']['common']['save'])->toBe('حفظ');
    expect($messages['core']['auth']['log_in'])->toBe('تسجيل الدخول');
    expect($messages['validation']['required'])->toBe('حقل :attribute مطلوب.');
});

test('the arabic dictionary falls back to english for untranslated keys', function () {
    // `accepted_if` is only defined in the English validation file.
    expect(Translations::for('ar')['validation']['accepted_if'])
        ->toBe(__('validation.accepted_if', [], 'en'));
});

test('the locale falls back to the application default', function () {
    $this->get(route('home'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('locale', 'en')
            ->where('direction', 'ltr')
    );
});

test('the locale is resolved from the cookie', function () {
    $this->withUnencryptedCookie('locale', 'ar')->get(route('home'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('locale', 'ar')
            ->where('direction', 'rtl')
    );
});

test('the locale is resolved from the signed in user', function () {
    $user = User::factory()->create(['locale' => 'ar']);

    $this->actingAs($user)->get(route('home'))->assertInertia(
        fn (AssertableInertia $page) => $page->where('locale', 'ar')
    );
});

test('the cookie takes precedence over the user preference', function () {
    $user = User::factory()->create(['locale' => 'ar']);

    $this->actingAs($user)->withUnencryptedCookie('locale', 'en')->get(route('home'))->assertInertia(
        fn (AssertableInertia $page) => $page->where('locale', 'en')
    );
});

test('the locale is resolved from the accept language header', function () {
    $this->withHeader('Accept-Language', 'ar-SA,ar;q=0.9,en;q=0.8')
        ->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('locale', 'ar'));
});

test('an unsupported browser language falls back to the default', function () {
    $this->withHeader('Accept-Language', 'de-DE,de;q=0.9')
        ->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('locale', 'en'));
});

test('the root template carries the resolved direction', function () {
    $this->withUnencryptedCookie('locale', 'ar')->get(route('home'))
        ->assertSee('dir="rtl"', escape: false);
});

test('the dictionary is shared with the initial page load', function () {
    $this->get(route('home'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('translations.core.common.save', 'Save')
            ->where('translations.validation.required', 'The :attribute field is required.')
    );
});

test('the shared dictionary follows the resolved locale', function () {
    $this->withUnencryptedCookie('locale', 'ar')->get(route('home'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('translations.core.common.save', 'حفظ')
            ->where('translations.core.auth.log_in', 'تسجيل الدخول')
    );
});

test('the dictionary is a once prop so it is not resent on later visits', function () {
    $response = $this->get(route('home'));

    $key = 'translations.en.'.Locale::version();
    expect($response->viewData('page')['onceProps'] ?? [])->toHaveKey($key);
});

test('switching locale is remembered on the signed in user', function () {
    $user = User::factory()->create(['locale' => null]);

    $this->actingAs($user)->withUnencryptedCookie('locale', 'ar')->get(route('home'));

    expect($user->fresh()->locale)->toBe('ar');
});

test('the locale is resolved from the signed in admin', function () {
    $admin = Admin::factory()->create(['locale' => 'ar']);

    $this->actingAs($admin, 'super')->get(route('super.dashboard'))->assertInertia(
        fn (AssertableInertia $page) => $page->where('locale', 'ar')
    );
});

test('switching locale is remembered on the signed in admin', function () {
    $admin = Admin::factory()->create(['locale' => null]);

    $this->actingAs($admin, 'super')
        ->withUnencryptedCookie('locale', 'ar')
        ->get(route('super.dashboard'))
        ->assertSuccessful();

    expect($admin->fresh()->locale)->toBe('ar');
});

test('the supported locales are shared with the page', function () {
    $this->get(route('home'))->assertInertia(
        fn (AssertableInertia $page) => $page->where('supportedLocales', Locale::SUPPORTED)
    );
});

test('locale metadata reports the correct direction', function () {
    expect(Locale::direction('ar'))->toBe('rtl');
    expect(Locale::direction('en'))->toBe('ltr');
    expect(Locale::isSupported('ar'))->toBeTrue();
    expect(Locale::isSupported('de'))->toBeFalse();
    expect(Locale::isSupported(null))->toBeFalse();
});
