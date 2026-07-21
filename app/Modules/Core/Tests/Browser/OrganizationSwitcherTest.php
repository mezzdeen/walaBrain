<?php

use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;

/*
|--------------------------------------------------------------------------
| Organization Switcher Browser Tests
|--------------------------------------------------------------------------
|
| Knowing which organization you are acting in is the whole point of the
| control, and that is only true of what actually renders. These drive the
| compiled bundle in a real browser rather than asserting on page props.
|
*/

/**
 * A user who belongs to both of the given organizations, holding a role in
 * each — which is what makes the switcher appear at all.
 */
function memberOfBoth(Organization $first, Organization $second): User
{
    return tap(memberOf($first), function (User $user) use ($second): void {
        $user->organizations()->attach($second);
    });
}

test('the sidebar names the organization the user is working in', function () {
    $acme = Organization::factory()->create(['name' => 'Acme Corp']);

    $this->actingAs(memberOf($acme));

    visit(route('dashboard', absolute: false))
        ->assertSee('Acme Corp')
        ->assertNoJavaScriptErrors();
});

test('a user with a single organization is offered no switcher', function () {
    $this->actingAs(memberOf(Organization::factory()->create(['name' => 'Acme Corp'])));

    // A menu holding only the organization you are already in would be a
    // control that does nothing.
    visit(route('dashboard', absolute: false))
        ->assertSee('Acme Corp')
        ->assertMissing('@org-switcher-trigger')
        ->assertNoJavaScriptErrors();
});

test('a user in several organizations is offered the switcher', function () {
    $acme = Organization::factory()->create(['name' => 'Acme Corp']);
    $globex = Organization::factory()->create(['name' => 'Globex']);

    $this->actingAs(memberOfBoth($acme, $globex));

    visit(route('dashboard', absolute: false))
        ->click('@org-switcher-trigger')
        ->assertSee('Globex')
        ->assertNoJavaScriptErrors();
});

test('switching organization changes what the sidebar reports', function () {
    $acme = Organization::factory()->create(['name' => 'Acme Corp']);
    $globex = Organization::factory()->create(['name' => 'Globex']);

    $this->actingAs(memberOfBoth($acme, $globex));

    visit(route('profile.edit', absolute: false))
        ->click('@org-switcher-trigger')
        ->click("@org-switcher-option-{$globex->id}")
        // The switch redirects, so the assertions below have to wait for the
        // new page rather than racing the one being replaced.
        ->waitForEvent('networkidle')
        ->assertUrlIs(route('dashboard'))
        ->assertSee('Globex')
        ->assertNoJavaScriptErrors();
});

test('the switcher renders without javascript errors in arabic', function () {
    $acme = Organization::factory()->create(['name' => 'Acme Corp']);
    $globex = Organization::factory()->create(['name' => 'Globex']);

    $user = tap(memberOfBoth($acme, $globex))->update(['locale' => 'ar']);

    $this->actingAs($user);

    visit(route('dashboard', absolute: false))
        ->assertAttribute(':root', 'dir', 'rtl')
        ->click('@org-switcher-trigger')
        ->assertSee('تبديل المؤسسة')
        ->assertNoJavaScriptErrors();
});
