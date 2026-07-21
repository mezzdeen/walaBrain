<?php

use App\Modules\Core\Enums\OrganizationRole;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\HashId;
use Inertia\Testing\AssertableInertia as Assert;
use Sqids\Sqids;

test('a record is addressed by a short opaque code rather than its key', function () {
    $organization = Organization::factory()->create();

    expect($organization->getRouteKey())
        ->toBeString()
        ->toHaveLength(5)
        ->not->toBe((string) $organization->id);
});

test('every record gets its own code', function () {
    $codes = Organization::factory()->count(20)->create()
        ->map(fn (Organization $organization): string => $organization->getRouteKey());

    expect($codes->unique())->toHaveCount(20);
});

test('a code decodes back to the key it was made from', function () {
    $organization = Organization::factory()->create();

    expect(HashId::decode($organization->getRouteKey()))->toBe($organization->id);
});

test('codes are not issued from the library default alphabet', function () {
    // The whole point of configuring one. `Sqids` defaults every constructor
    // argument, so anything that reaches for it without passing the configured
    // alphabet still returns an encoder — one whose codes anybody who
    // recognises the format can reverse. Nothing throws when that happens, so
    // this is the only thing standing between that mistake and production.
    $default = (new Sqids(alphabet: Sqids::DEFAULT_ALPHABET, minLength: 5, blocklist: []))->encode([1]);

    expect(HashId::encode(1))->not->toBe($default);
});

test('the encoder follows the configured alphabet rather than caching the first one', function () {
    $before = HashId::encode(1);

    config(['core.hash_id.alphabet' => strrev((string) config('core.hash_id.alphabet'))]);

    expect(HashId::encode(1))->not->toBe($before);
});

test('a key that was never loaded yields no code rather than a wrong one', function () {
    // A column list without `id` used to be harmless. It now decides whether a
    // row has an address at all, and every such row sharing one code would be
    // far worse than none having one.
    Organization::factory()->count(2)->create();

    $withoutKeys = Organization::query()->select('name')->get();

    expect($withoutKeys)->toHaveCount(2)
        ->and($withoutKeys->pluck('hash_id')->all())->toBe([null, null])
        ->and($withoutKeys->first()->toArray())->toMatchArray(['hash_id' => null])
        ->and((new Organization(['name' => 'Unsaved']))->hash_id)->toBeNull();
});

test('routes are generated with the code', function () {
    $organization = Organization::factory()->create();

    expect(route('organizations.switch', $organization))
        ->toContain($organization->getRouteKey())
        ->not->toContain("/organizations/{$organization->id}/");
});

test('a code resolves to its record', function () {
    $organization = Organization::factory()->create();
    $owner = memberOf($organization, OrganizationRole::Owner);

    $this->actingAs($owner)
        ->put(route('organizations.switch', $organization))
        ->assertRedirect();
});

test('the underlying key is not accepted in place of the code', function () {
    $organization = Organization::factory()->create();
    $owner = memberOf($organization, OrganizationRole::Owner);

    $this->actingAs($owner)
        ->put("/organizations/{$organization->id}/switch")
        ->assertNotFound();
});

test('a code for a record that does not exist is refused', function () {
    $organization = Organization::factory()->create();
    $owner = memberOf($organization, OrganizationRole::Owner);

    $this->actingAs($owner)
        ->put('/organizations/'.HashId::encode(999_999).'/switch')
        ->assertNotFound();
});

test('a value we would never have issued is refused', function (string $value) {
    $organization = Organization::factory()->create();
    $owner = memberOf($organization, OrganizationRole::Owner);

    $this->actingAs($owner)
        ->put("/organizations/{$value}/switch")
        ->assertNotFound();
})->with([
    'nonsense' => 'zzzzz',
    'empty-ish' => '-',
    'the wrong alphabet' => '!!!!!',
    // Longer than we issue for this key, and so a second address for one
    // record if it were honoured. The re-encode check is what refuses it.
    'a non-canonical form' => 'aaaaaaaaaaaa',
]);

test('the numeric key never reaches the browser', function () {
    $organization = Organization::factory()->create(['name' => 'Acme']);
    $owner = memberOf($organization, OrganizationRole::Owner);

    $this->actingAs($owner)
        ->get(route('organization.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->missing('auth.user.id')
            ->missing('organization.id')
            ->missing('organizations.0.id')
            ->where('organization.hash_id', $organization->getRouteKey())
            ->where('organizations.0.hash_id', $organization->getRouteKey())
            ->where('auth.user.hash_id', $owner->getRouteKey())
        );
});

test('the numeric key never reaches the admin platform either', function () {
    // The admin identity is shared as a whole model rather than projected, so
    // only the model's own hiding keeps its key off the page.
    $admin = superAdmin();

    $this->actingAs($admin, 'super')
        ->get(route('super.organizations.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->missing('auth.admin.id')
            ->where('auth.admin.hash_id', $admin->getRouteKey())
        );
});

test('the user search returns codes rather than keys', function () {
    $admin = superAdmin();
    $match = User::factory()->create(['email' => 'ada@example.com']);

    $response = $this->actingAs($admin, 'super')
        ->getJson(route('super.users.search', ['q' => 'ada']));

    expect($response->json('users.0'))->not->toHaveKey('id')
        ->and($response->json('users.0.hash_id'))->toBe($match->getRouteKey());
});

test('hiding the key from serialization leaves it usable in php', function () {
    // The permission package reads roles through `getAttributes()` and the
    // organization context holds a key in the session, so both would break if
    // hiding it from JSON hid it from the application too.
    $organization = Organization::factory()->create();

    expect($organization->getKey())->toBeInt()
        ->and($organization->id)->toBe($organization->getKey())
        ->and($organization->only(['id']))->toBe(['id' => $organization->getKey()])
        ->and($organization->getAttributes())->toHaveKey('id');
});
