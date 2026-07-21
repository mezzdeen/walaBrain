<?php

use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;

/*
|--------------------------------------------------------------------------
| Brand Colour Browser Tests
|--------------------------------------------------------------------------
|
| The colour is applied by a stylesheet the server writes and the client keeps
| in step, so nothing about it is visible to a test that asserts on page props.
| These drive the compiled bundle in a real browser instead.
|
| Every assertion is scoped to the `#brand-color` element rather than the whole
| page source. Inertia leaves the initial page's JSON in the root element's
| `data-page` attribute and never rewrites it, so an unscoped search still finds
| the colour the page was first loaded with long after it stopped applying.
|
*/

/**
 * A user who belongs to both of the given organizations, so they can switch
 * between them.
 */
function brandMemberOfBoth(Organization $first, Organization $second): User
{
    return tap(memberOf($first), function (User $user) use ($second): void {
        $user->organizations()->attach($second);
    });
}

test('the brand colour is in the page before the bundle runs', function () {
    $this->actingAs(memberOf(Organization::factory()->create(['color' => '#ca8a04'])));

    visit(route('dashboard', absolute: false))
        ->assertSourceInHas('#brand-color', '--primary: #ca8a04')
        ->assertNoJavaScriptErrors();
});

test('an organization with no colour is left with the default theme', function () {
    $this->actingAs(memberOf(Organization::factory()->create(['color' => null])));

    visit(route('dashboard', absolute: false))
        ->assertSourceInMissing('#brand-color', '--primary')
        ->assertNoJavaScriptErrors();
});

// The regression this exists for: `withApp` is handed the page once at mount
// and never again, so a brand colour read from there stayed on screen after
// switching organization. Switching redirects to the dashboard from the
// dashboard, which makes it a history replacement — and Inertia fires no
// `navigate` event for those, only `success`.
test('switching organization drops the previous brand colour', function () {
    $coloured = Organization::factory()->create(['name' => 'Acme Corp', 'color' => '#ca8a04']);
    $plain = Organization::factory()->create(['name' => 'Globex', 'color' => null]);

    $this->actingAs(brandMemberOfBoth($coloured, $plain));

    visit(route('dashboard', absolute: false))
        ->assertSourceInHas('#brand-color', '--primary: #ca8a04')
        ->click('@org-switcher-trigger')
        ->click("@org-switcher-option-{$plain->id}")
        // The switch redirects, so the assertions have to wait for the new page
        // rather than racing the one being replaced.
        ->waitForEvent('networkidle')
        ->assertSee('Globex')
        ->assertSourceInMissing('#brand-color', '--primary')
        ->assertNoJavaScriptErrors();
});

test('switching organization picks up the next one s brand colour', function () {
    $first = Organization::factory()->create(['name' => 'Acme Corp', 'color' => '#ca8a04']);
    $second = Organization::factory()->create(['name' => 'Globex', 'color' => '#2563eb']);

    $this->actingAs(brandMemberOfBoth($first, $second));

    visit(route('dashboard', absolute: false))
        ->click('@org-switcher-trigger')
        ->click("@org-switcher-option-{$second->id}")
        ->waitForEvent('networkidle')
        ->assertSourceInHas('#brand-color', '--primary: #2563eb')
        ->assertSourceInMissing('#brand-color', '#ca8a04')
        ->assertNoJavaScriptErrors();
});
