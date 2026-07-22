<?php

use App\Modules\Core\Models\User;
use App\Modules\Core\Support\PlatformSettings;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * The details a valid sign-up posts.
 *
 * @param  array<string, string>  $overrides
 * @return array<string, string>
 */
function registration(array $overrides = []): array
{
    return [
        'first_name' => 'Asmaa',
        'last_name' => 'Ezz',
        'email' => 'asmaa@example.com',
        'password' => 'correct-horse-battery-staple',
        'password_confirmation' => 'correct-horse-battery-staple',
        ...$overrides,
    ];
}

function openRegistration(): void
{
    PlatformSettings::update([PlatformSettings::RegistrationOpen => true]);
}

test('the sign-up page does not exist while the platform is closed', function () {
    $this->get(route('register'))->assertNotFound();
});

test('the platform cannot be signed up to while it is closed', function () {
    $this->post(route('register.store'), registration())->assertNotFound();

    expect(User::query()->count())->toBe(0);
});

test('the sign-up page is served once the platform is opened', function () {
    openRegistration();

    $this->get(route('register'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/register'));
});

test('the login page only offers a way to sign up while the platform is open', function () {
    $this->get(route('login'))
        ->assertInertia(fn (Assert $page) => $page->where('registration.open', false));

    openRegistration();

    $this->get(route('login'))
        ->assertInertia(fn (Assert $page) => $page->where('registration.open', true));
});

test('signing up creates an unverified user and asks them to confirm', function () {
    openRegistration();
    Event::fake([Registered::class]);

    $this->post(route('register.store'), registration())
        ->assertRedirect(route('verification.notice'));

    $user = User::query()->sole();

    expect($user->email)->toBe('asmaa@example.com')
        ->and($user->full_name)->toBe('Asmaa Ezz')
        ->and($user->hasVerifiedEmail())->toBeFalse()
        ->and($user->organizations()->count())->toBe(0);

    $this->assertAuthenticatedAs($user);
    Event::assertDispatched(Registered::class);
});

test('signing up sends the confirmation mail', function () {
    openRegistration();
    Notification::fake();

    $this->post(route('register.store'), registration());

    Notification::assertSentTo(User::query()->sole(), VerifyEmail::class);
});

test('an unverified user is held at the confirmation screen', function () {
    openRegistration();

    $this->post(route('register.store'), registration());

    $this->get(route('dashboard'))->assertRedirect(route('verification.notice'));
});

test('an unverified user cannot reach any of the application', function () {
    seedPermissions();

    $user = User::factory()->unverified()->create();

    // Every signed-in company screen, not just the dashboard: confirming the
    // address is the price of entry, so there is no corner of the application
    // that answers before it is done.
    $screens = [
        'dashboard',
        'profile.edit',
        'organizations.none',
        'appearance.edit',
        'security.edit',
    ];

    foreach ($screens as $screen) {
        $this->actingAs($user)
            ->get(route($screen))
            ->assertRedirect(route('verification.notice'));
    }
});

test('confirming the address opens the application up', function () {
    seedPermissions();

    $user = User::factory()->unverified()->create();
    $user->forceFill(['email_verified_at' => now()])->save();

    $this->actingAs($user)->get(route('profile.edit'))->assertOk();
});

test('a disposable address is refused', function () {
    openRegistration();

    $this->from(route('register'))
        ->post(route('register.store'), registration(['email' => 'asmaa@mailinator.com']))
        ->assertSessionHasErrors('email');

    expect(User::query()->count())->toBe(0);
});

test('a subdomain of a disposable provider is refused', function () {
    openRegistration();

    $this->from(route('register'))
        ->post(route('register.store'), registration(['email' => 'asmaa@team.mailinator.com']))
        ->assertSessionHasErrors('email');
});

test('an address that already has an account is refused', function () {
    openRegistration();
    User::factory()->create(['email' => 'asmaa@example.com']);

    $this->from(route('register'))
        ->post(route('register.store'), registration())
        ->assertSessionHasErrors('email');

    expect(User::query()->count())->toBe(1);
});

test('closing the platform does not strand someone who signed up while it was open', function () {
    openRegistration();
    $this->post(route('register.store'), registration());

    $user = User::query()->sole();

    PlatformSettings::update([PlatformSettings::RegistrationOpen => false]);

    // The confirmation routes deliberately do not carry the `registration`
    // middleware, so the link that was already mailed still works.
    $this->actingAs($user)->get(route('verification.notice'))->assertOk();
});

test('a mixed-case address is stored lower-cased so sign-in can find it', function () {
    openRegistration();

    $this->post(route('register.store'), registration(['email' => 'Asmaa@Example.com']))
        ->assertRedirect();

    // Stored lower-cased: Fortify matches the sign-in address in lower case, so
    // an account kept under the mixed-case spelling could never be reached.
    expect(User::query()->where('email', 'asmaa@example.com')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'Asmaa@Example.com')->exists())->toBeFalse();
});

test('a mixed-case address can not slip past the uniqueness check', function () {
    openRegistration();
    User::factory()->create(['email' => 'asmaa@example.com']);

    $this->from(route('register'))
        ->post(route('register.store'), registration(['email' => 'Asmaa@Example.com']))
        ->assertSessionHasErrors('email');

    expect(User::query()->where('email', 'asmaa@example.com')->count())->toBe(1);
});
