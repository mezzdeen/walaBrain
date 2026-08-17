<?php

use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\OrganizationContext;
use Illuminate\Notifications\Notification as BaseNotification;

/*
|--------------------------------------------------------------------------
| Notification Bell Browser Tests
|--------------------------------------------------------------------------
|
| The centre's whole promise is that being told something in one language
| renders in whichever language the reader chose, and that a notification
| is one click from the thing it is about. Both are only true of what
| actually renders, so these drive the compiled bundle in a real browser.
|
*/

/**
 * A notification shaped the way modules write them: a key, its parameters,
 * and a deep link. The key is a real dictionary line with a parameter, so
 * the test proves the bell resolves and substitutes rather than echoing.
 */
class DecisionIsWaiting extends BaseNotification
{
    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'key' => 'core.notifications.unread_count',
            'params' => ['count' => 7],
            'url' => route('profile.edit', absolute: false),
        ];
    }
}

/**
 * A member of a fresh organization with one unread notification waiting.
 */
function memberWithNotification(): User
{
    $organization = Organization::factory()->create();

    return tap(memberOf($organization), function (User $member) use ($organization): void {
        OrganizationContext::for($organization, function () use ($member): void {
            $member->notify(new DecisionIsWaiting);
        });
    });
}

test('the bell counts what is unread', function () {
    $this->actingAs(memberWithNotification());

    visit(route('dashboard', absolute: false))
        ->assertVisible('@notification-bell')
        ->assertSeeIn('@notification-badge', '1 unread')
        ->assertNoJavaScriptErrors();
});

test('opening the bell renders the line in the reader\'s language', function () {
    $this->actingAs(memberWithNotification());

    visit(route('dashboard', absolute: false))
        ->click('@notification-bell')
        ->assertSee('7 unread')
        ->assertNoJavaScriptErrors();
});

test('opening a notification follows its deep link', function () {
    $this->actingAs(memberWithNotification());

    visit(route('dashboard', absolute: false))
        ->click('@notification-bell')
        ->click('@notification-item')
        // Marking it read redirects back before the deep link is followed,
        // so wait for the dust to settle rather than racing it.
        ->waitForEvent('networkidle')
        ->assertUrlIs(route('profile.edit'))
        ->assertNoJavaScriptErrors();
});

test('marking everything read clears the badge', function () {
    $this->actingAs(memberWithNotification());

    visit(route('dashboard', absolute: false))
        ->click('@notification-bell')
        ->click('@notifications-mark-all-read')
        ->assertMissing('@notification-badge')
        ->assertNoJavaScriptErrors();
});

test('dismissing the only notification leaves the caught-up state', function () {
    $this->actingAs(memberWithNotification());

    visit(route('dashboard', absolute: false))
        ->click('@notification-bell')
        ->click('@notification-dismiss')
        ->assertVisible('@notifications-empty')
        ->assertNoJavaScriptErrors();
});

test('the bell renders in arabic without javascript errors', function () {
    $member = tap(memberWithNotification())->update(['locale' => 'ar']);

    $this->actingAs($member);

    visit(route('dashboard', absolute: false))
        ->assertAttribute(':root', 'dir', 'rtl')
        ->click('@notification-bell')
        ->assertSee('7 غير مقروء')
        ->assertNoJavaScriptErrors();
});
