<?php

use App\Modules\Core\Enums\SuperPermission;
use App\Modules\Core\Models\User;
use App\Modules\Core\Notifications\MailDeliveryCheck;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

test('an admin can send themselves a mail delivery check', function () {
    Notification::fake();
    $admin = superAdmin();

    $this->actingAs($admin, 'super')
        ->getJson(route('super.diagnostics.mail'))
        ->assertOk()
        ->assertJsonPath('queued', true)
        ->assertJsonPath('recipient', $admin->email);

    Notification::assertSentTo($admin, MailDeliveryCheck::class);
});

test('the check is queued rather than sent inline', function () {
    expect(MailDeliveryCheck::class)->toImplement(ShouldQueue::class);
});

test('the response reports the settings actually in force', function () {
    Notification::fake();
    config([
        'mail.default' => 'smtp',
        'mail.mailers.smtp.host' => 'sandbox.smtp.mailtrap.io',
        'mail.mailers.smtp.port' => 587,
        'mail.from.address' => 'hello@example.com',
    ]);

    $response = $this->actingAs(superAdmin(), 'super')
        ->getJson(route('super.diagnostics.mail'));

    $response->assertOk()
        ->assertJsonPath('settings.mailer', 'smtp')
        ->assertJsonPath('settings.host', 'sandbox.smtp.mailtrap.io')
        ->assertJsonPath('settings.port', 587)
        ->assertJsonPath('settings.from', 'hello@example.com');
});

test('the response never exposes the mail credentials', function () {
    Notification::fake();
    config([
        'mail.default' => 'smtp',
        'mail.mailers.smtp.username' => 'a-real-username',
        'mail.mailers.smtp.password' => 'a-real-password',
    ]);

    $response = $this->actingAs(superAdmin(), 'super')
        ->getJson(route('super.diagnostics.mail'));

    expect($response->getContent())
        ->not->toContain('a-real-username')
        ->not->toContain('a-real-password');
});

test('the mail carries the settings that sent it', function () {
    Notification::fake();
    $admin = superAdmin();
    config([
        'mail.default' => 'smtp',
        'mail.mailers.smtp.host' => 'sandbox.smtp.mailtrap.io',
        'mail.mailers.smtp.port' => 587,
    ]);

    $this->actingAs($admin, 'super')->getJson(route('super.diagnostics.mail'));

    Notification::assertSentTo($admin, MailDeliveryCheck::class, function (MailDeliveryCheck $notification) use ($admin) {
        $body = (string) $notification->toMail($admin)->render();

        return str_contains($body, 'sandbox.smtp.mailtrap.io') && str_contains($body, '587');
    });
});

test('the check goes to the requesting admin, not an address in the request', function () {
    Notification::fake();
    $admin = superAdmin();

    $this->actingAs($admin, 'super')
        ->getJson(route('super.diagnostics.mail', ['email' => 'attacker@example.com']))
        ->assertOk()
        ->assertJsonPath('recipient', $admin->email);

    Notification::assertSentTo($admin, MailDeliveryCheck::class);
    Notification::assertCount(1);
});

test('an admin without the diagnostics permission can not run the check', function () {
    Notification::fake();
    $admin = adminWith(SuperPermission::ViewOrganizations);

    $this->actingAs($admin, 'super')
        ->getJson(route('super.diagnostics.mail'))
        ->assertForbidden();

    Notification::assertNothingSent();
});

test('guests can not run the check', function () {
    Notification::fake();

    $this->get(route('super.diagnostics.mail'))
        ->assertRedirect(route('super.login'));

    Notification::assertNothingSent();
});

test('company users can not run the check', function () {
    Notification::fake();

    $this->actingAs(User::factory()->create())
        ->get(route('super.diagnostics.mail'))
        ->assertRedirect(route('super.login'));

    Notification::assertNothingSent();
});
