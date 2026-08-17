<?php

use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;
use App\Modules\Core\Services\OrganizationService;

/*
|--------------------------------------------------------------------------
| Working Language
|--------------------------------------------------------------------------
|
| The language an organization's own people write in, as opposed to the one
| each person reads the interface in. The two are independent on purpose.
|
*/

test('an organization has a working language', function () {
    $organization = Organization::factory()->create();

    expect($organization->locale)->toBe('en');
});

test('someone signing themselves up sets the language they write in', function () {
    $owner = User::factory()->create(['locale' => 'ar']);

    $organization = app(OrganizationService::class)->createForOwner($owner, 'التسويق');

    expect($organization->locale)->toBe('ar');
});

test('an owner with no language of their own falls back to the platform default', function () {
    $owner = User::factory()->create(['locale' => null]);

    $organization = app(OrganizationService::class)->createForOwner($owner, 'Marketing');

    expect($organization->locale)->toBe(config('app.locale'));
});

test('an admin can name the language when creating an organization', function () {
    $admin = superAdmin();

    $this->actingAs($admin, 'super')
        ->post(route('super.organizations.store'), [
            'name' => 'التسويق',
            'owner_email' => 'lead@example.com',
            'locale' => 'ar',
        ])
        ->assertRedirect(route('super.organizations.index'));

    expect(Organization::query()->firstWhere('name', 'التسويق')->locale)->toBe('ar');
});

test('an unsupported language is refused', function () {
    $admin = superAdmin();

    $this->actingAs($admin, 'super')
        ->post(route('super.organizations.store'), [
            'name' => 'Marketing',
            'owner_email' => 'lead@example.com',
            'locale' => 'fr',
        ])
        ->assertSessionHasErrors('locale');
});

test('leaving the language out falls back rather than failing', function () {
    $admin = superAdmin();

    $this->actingAs($admin, 'super')
        ->post(route('super.organizations.store'), [
            'name' => 'Marketing',
            'owner_email' => 'lead@example.com',
        ])
        ->assertSessionHasNoErrors();

    expect(Organization::query()->firstWhere('name', 'Marketing')->locale)->toBe(config('app.locale'));
});

test('the interface language a person reads in is their own, not the organization\'s', function () {
    $organization = Organization::factory()->create(['locale' => 'ar']);
    $member = memberOf($organization);
    $member->update(['locale' => 'en']);

    // Someone reading English chrome inside an Arabic organization is a
    // supported combination, not a conflict to resolve.
    expect($member->preferredLocale())->toBe('en')
        ->and($organization->locale)->toBe('ar');
});
