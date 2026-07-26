<?php

use App\Modules\Core\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('profile.edit'));

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            // Required now that the address is changing: it is what a reset link
            // is sent to, so the account password gates the change.
            'current_password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->full_name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('profile.destroy'), [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    $this->assertGuest();
    $this->assertSoftDeleted('users', ['id' => $user->id]);
});

test('a deleted account can no longer be signed in to', function () {
    $user = User::factory()->create(['email' => 'gone@example.com']);
    $user->delete();

    $this->post(route('login'), [
        'email' => 'gone@example.com',
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh())->not->toBeNull();
});

test('a mixed-case email is stored lower-cased on profile update', function () {
    $user = User::factory()->create(['email' => 'old@example.com']);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'New@Example.com',
            'current_password' => 'password',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    // Lower-cased so the address still matches at the next sign-in, which
    // Fortify performs in lower case.
    expect($user->fresh()->email)->toBe('new@example.com');
});

test('changing the email requires the current password', function () {
    $user = User::factory()->create(['email' => 'old@example.com']);

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'new@example.com',
            // No current_password: a borrowed session must not be able to move
            // the address to an inbox the attacker controls.
        ])
        ->assertSessionHasErrors('current_password')
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh()->email)->toBe('old@example.com');
});

test('changing the email with the wrong current password is refused', function () {
    $user = User::factory()->create(['email' => 'old@example.com']);

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'new@example.com',
            'current_password' => 'not-the-password',
        ])
        ->assertSessionHasErrors('current_password');

    expect($user->fresh()->email)->toBe('old@example.com');
});

test('a name-only change needs no password', function () {
    $user = User::factory()->create(['email' => 'keep@example.com']);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'first_name' => 'Renamed',
            'last_name' => 'Person',
            'email' => 'keep@example.com',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh()->first_name)->toBe('Renamed');
});

test('changing the email sends a verification mail to the new address', function () {
    Notification::fake();

    $user = User::factory()->create(['email' => 'old@example.com']);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'new@example.com',
            'current_password' => 'password',
        ])
        ->assertSessionHasNoErrors();

    expect($user->fresh()->email_verified_at)->toBeNull();

    // Sent, so the account is not stranded behind the verified middleware with
    // no mail to act on.
    Notification::assertSentTo($user->fresh(), VerifyEmail::class);
});

test('a change of case in the email is not treated as a change', function () {
    $user = User::factory()->create(['email' => 'person@example.com']);

    // Same address, different spelling, and no password: it resolves to the
    // same account, so it is not the sensitive change the password guards.
    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'Person@Example.com',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));
});
