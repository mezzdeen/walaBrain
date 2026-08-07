<?php

use App\Modules\Core\Models\Notification;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\OrganizationContext;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as BaseNotification;

/*
|--------------------------------------------------------------------------
| Notification Centre
|--------------------------------------------------------------------------
|
| The in-app half of delivery. Email is the channel people watch; this is
| what keeps an item findable once the email has been scrolled past, and it
| has to respect the business line someone is working in.
|
*/

class SomethingHappened extends BaseNotification
{
    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return ['reference' => 'FIN-2026-0107'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->line('Something happened.');
    }
}

test('a notification raised inside an organization is stamped with it', function () {
    $organization = Organization::factory()->create();
    $member = memberOf($organization);

    OrganizationContext::for($organization, function () use ($member): void {
        $member->notify(new SomethingHappened);
    });

    $notification = Notification::query()->firstOrFail();

    expect($notification->organization_id)->toBe($organization->getKey())
        ->and($notification->data)->toBe(['reference' => 'FIN-2026-0107']);
});

test('a notification raised outside an organization belongs to none', function () {
    $user = User::factory()->create();

    OrganizationContext::useGlobal();

    $user->notify(new SomethingHappened);

    expect(Notification::query()->firstOrFail()->organization_id)->toBeNull();
});

test('a notification about one organization is invisible from another', function () {
    $marketing = Organization::factory()->create();
    $finance = Organization::factory()->create();

    $person = memberOf($marketing);
    $finance->users()->attach($person);

    OrganizationContext::for($marketing, function () use ($person): void {
        $person->notify(new SomethingHappened);
    });

    expect($person->notificationsIn($marketing)->count())->toBe(1)
        ->and($person->notificationsIn($finance)->count())->toBe(0);
});

test('a notification about the person themselves is visible from everywhere', function () {
    $organization = Organization::factory()->create();
    $member = memberOf($organization);

    OrganizationContext::useGlobal();
    $member->notify(new SomethingHappened);

    // Belongs to no organization, so it would otherwise be reachable from
    // nowhere at all.
    expect($member->notificationsIn($organization)->count())->toBe(1)
        ->and($member->notificationsIn(null)->count())->toBe(1);
});

test('the centre lists what is waiting and how much of it is unread', function () {
    $organization = Organization::factory()->create();
    $member = memberOf($organization);

    OrganizationContext::for($organization, function () use ($member): void {
        $member->notify(new SomethingHappened);
        $member->notify(new SomethingHappened);
    });

    OrganizationContext::switch($organization);

    $this->actingAs($member)
        ->getJson(route('notifications.index'))
        ->assertOk()
        ->assertJsonCount(2, 'notifications')
        ->assertJsonPath('unread', 2);
});

test('opening a notification marks it seen', function () {
    $organization = Organization::factory()->create();
    $member = memberOf($organization);

    OrganizationContext::for($organization, function () use ($member): void {
        $member->notify(new SomethingHappened);
    });

    $notification = Notification::query()->firstOrFail();

    OrganizationContext::switch($organization);

    $this->actingAs($member)
        ->patch(route('notifications.update', $notification->id))
        ->assertRedirect();

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('everything visible can be marked seen at once', function () {
    $organization = Organization::factory()->create();
    $member = memberOf($organization);

    OrganizationContext::for($organization, function () use ($member): void {
        $member->notify(new SomethingHappened);
        $member->notify(new SomethingHappened);
    });

    OrganizationContext::switch($organization);

    $this->actingAs($member)->post(route('notifications.read-all'))->assertRedirect();

    expect($member->notificationsIn($organization)->unread()->count())->toBe(0);
});

test('a notification can be dismissed', function () {
    $organization = Organization::factory()->create();
    $member = memberOf($organization);

    OrganizationContext::for($organization, function () use ($member): void {
        $member->notify(new SomethingHappened);
    });

    $notification = Notification::query()->firstOrFail();

    OrganizationContext::switch($organization);

    $this->actingAs($member)
        ->delete(route('notifications.destroy', $notification->id))
        ->assertRedirect();

    expect(Notification::query()->count())->toBe(0);
});

test('somebody else\'s notification is not found rather than acted on', function () {
    $organization = Organization::factory()->create();
    $owner = memberOf($organization);
    $intruder = memberOf($organization);

    OrganizationContext::for($organization, function () use ($owner): void {
        $owner->notify(new SomethingHappened);
    });

    $notification = Notification::query()->firstOrFail();

    OrganizationContext::switch($organization);

    $this->actingAs($intruder)
        ->patch(route('notifications.update', $notification->id))
        ->assertNotFound();

    expect($notification->fresh()->read_at)->toBeNull();
});

test('a notification from another organization cannot be reached from this one', function () {
    $marketing = Organization::factory()->create();
    $finance = Organization::factory()->create();

    $person = memberOf($marketing);
    $finance->users()->attach($person);

    OrganizationContext::for($marketing, function () use ($person): void {
        $person->notify(new SomethingHappened);
    });

    $notification = Notification::query()->firstOrFail();

    // Their own notification, but not one belonging to the business line they
    // are currently working in.
    OrganizationContext::switch($finance);

    $this->actingAs($person)
        ->patch(route('notifications.update', $notification->id))
        ->assertNotFound();
});

test('a notification is delivered by email as well as in app', function () {
    $organization = Organization::factory()->create();
    $member = memberOf($organization);

    Illuminate\Support\Facades\Notification::fake();

    OrganizationContext::for($organization, function () use ($member): void {
        $member->notify(new SomethingHappened);
    });

    Illuminate\Support\Facades\Notification::assertSentTo(
        $member,
        SomethingHappened::class,
        fn (SomethingHappened $notification, array $channels): bool => in_array('mail', $channels, true)
            && in_array('database', $channels, true),
    );
});
