<?php

use App\Modules\Core\Database\Seeders\DemoSeeder;
use App\Modules\Core\Enums\OrganizationRole;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\OrganizationRoles;

/**
 * Every role the user holds in the organization.
 *
 * The whole list rather than the first one: a seeder that handed an account a
 * second role on top of the one it should have would still look correct to an
 * assertion that only ever read the first.
 *
 * @return list<string>
 */
function demoRoles(Organization $organization, User $user): array
{
    return OrganizationRoles::within(
        $organization,
        fn (): array => $user->fresh()?->roles->pluck('name')->sort()->values()->all() ?? [],
    );
}

test('the demo seeder creates both accounts and both organizations', function () {
    $this->seed(DemoSeeder::class);

    $this->assertDatabaseHas('users', ['email' => 'asmaa@app.com']);
    $this->assertDatabaseHas('users', ['email' => 'tysier@app.com']);
    $this->assertDatabaseHas('organizations', ['name' => 'Nakheel']);
    $this->assertDatabaseHas('organizations', ['name' => 'Rawabi']);
});

test('each demo account owns one organization and is a member of the other', function () {
    $this->seed(DemoSeeder::class);

    $asmaa = User::query()->firstWhere('email', 'asmaa@app.com');
    $tysier = User::query()->firstWhere('email', 'tysier@app.com');
    $nakheel = Organization::query()->firstWhere('name', 'Nakheel');
    $rawabi = Organization::query()->firstWhere('name', 'Rawabi');

    expect(demoRoles($nakheel, $asmaa))->toBe([OrganizationRole::Owner->value])
        ->and(demoRoles($nakheel, $tysier))->toBe([OrganizationRole::Member->value])
        // Reversed in the second organization, which is the whole point of the
        // fixture: the same account is an owner in one and a member in the other.
        ->and(demoRoles($rawabi, $tysier))->toBe([OrganizationRole::Owner->value])
        ->and(demoRoles($rawabi, $asmaa))->toBe([OrganizationRole::Member->value]);
});

test('both demo accounts belong to both organizations', function () {
    $this->seed(DemoSeeder::class);

    $asmaa = User::query()->firstWhere('email', 'asmaa@app.com');
    $tysier = User::query()->firstWhere('email', 'tysier@app.com');

    expect($asmaa->organizations->pluck('name')->sort()->values()->all())
        ->toBe(['Nakheel', 'Rawabi'])
        ->and($tysier->organizations->pluck('name')->sort()->values()->all())
        ->toBe(['Nakheel', 'Rawabi']);
});

test('the demo seeder can be run twice without duplicating anything', function () {
    $this->seed(DemoSeeder::class);
    $this->seed(DemoSeeder::class);

    $this->assertDatabaseCount('users', 2);
    $this->assertDatabaseCount('organizations', 2);
    $this->assertDatabaseCount('organization_user', 4);

    $nakheel = Organization::query()->firstWhere('name', 'Nakheel');
    $asmaa = User::query()->firstWhere('email', 'asmaa@app.com');

    // A second run must not leave the owner holding two roles at once.
    expect(demoRoles($nakheel, $asmaa))->toBe([OrganizationRole::Owner->value]);
});

test('the demo accounts can sign in with the seeded password', function () {
    $this->seed(DemoSeeder::class);

    $this->post(route('login'), [
        'email' => 'asmaa@app.com',
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
});
