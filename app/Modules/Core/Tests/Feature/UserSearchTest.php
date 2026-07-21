<?php

use App\Modules\Core\Enums\SuperPermission;
use App\Modules\Core\Models\User;

test('admins can find users by the start of their email', function () {
    $admin = superAdmin();
    $match = User::factory()->create(['email' => 'ada@example.com']);
    User::factory()->create(['email' => 'grace@example.com']);

    $response = $this->actingAs($admin, 'super')
        ->getJson(route('super.users.search', ['q' => 'ada']));

    $response->assertOk();
    expect($response->json('users'))->toHaveCount(1)
        ->and($response->json('users.0.hash_id'))->toBe($match->getRouteKey())
        ->and($response->json('users.0.email'))->toBe('ada@example.com');
});

test('admins can find users by part of their name', function () {
    $admin = superAdmin();
    $match = User::factory()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);
    User::factory()->create(['first_name' => 'Grace', 'last_name' => 'Hopper']);

    $response = $this->actingAs($admin, 'super')
        ->getJson(route('super.users.search', ['q' => 'ovelac']));

    $response->assertOk();
    expect($response->json('users'))->toHaveCount(1)
        ->and($response->json('users.0.hash_id'))->toBe($match->getRouteKey());
});

test('admins can find users by their whole name', function () {
    $admin = superAdmin();
    $match = User::factory()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);
    User::factory()->create(['first_name' => 'Grace', 'last_name' => 'Hopper']);

    $response = $this->actingAs($admin, 'super')
        ->getJson(route('super.users.search', ['q' => 'Ada Lov']));

    $response->assertOk();
    expect($response->json('users'))->toHaveCount(1)
        ->and($response->json('users.0.hash_id'))->toBe($match->getRouteKey());
});

test('the search only exposes the fields the typeahead renders', function () {
    $admin = superAdmin();
    User::factory()->create(['email' => 'ada@example.com']);

    $response = $this->actingAs($admin, 'super')
        ->getJson(route('super.users.search', ['q' => 'ada']));

    expect(array_keys($response->json('users.0')))
        ->toEqualCanonicalizing(['hash_id', 'first_name', 'last_name', 'full_name', 'email']);
});

test('a query shorter than two characters returns nothing', function () {
    $admin = superAdmin();
    User::factory()->create(['email' => 'ada@example.com']);

    $this->actingAs($admin, 'super')
        ->getJson(route('super.users.search', ['q' => 'a']))
        ->assertOk()
        ->assertExactJson(['users' => []]);
});

test('a missing query returns nothing', function () {
    $admin = superAdmin();
    User::factory()->create();

    $this->actingAs($admin, 'super')
        ->getJson(route('super.users.search'))
        ->assertOk()
        ->assertExactJson(['users' => []]);
});

test('the search caps how many users it returns', function () {
    $admin = superAdmin();
    User::factory()->count(12)->create([
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
    ]);

    $response = $this->actingAs($admin, 'super')
        ->getJson(route('super.users.search', ['q' => 'Ada']));

    expect($response->json('users'))->toHaveCount(8);
});

test('wildcards in the query are treated as literal characters', function () {
    $admin = superAdmin();
    User::factory()->create(['email' => 'ada@example.com', 'first_name' => 'Ada']);

    $this->actingAs($admin, 'super')
        ->getJson(route('super.users.search', ['q' => '%%']))
        ->assertOk()
        ->assertExactJson(['users' => []]);
});

test('an admin without the create permission can not search users', function () {
    $admin = adminWith(SuperPermission::ViewOrganizations);

    $this->actingAs($admin, 'super')
        ->getJson(route('super.users.search', ['q' => 'ada']))
        ->assertForbidden();
});

test('guests can not search users', function () {
    $this->get(route('super.users.search', ['q' => 'ada']))
        ->assertRedirect(route('super.login'));
});

test('company users can not search users', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('super.users.search', ['q' => 'ada']))
        ->assertRedirect(route('super.login'));
});
