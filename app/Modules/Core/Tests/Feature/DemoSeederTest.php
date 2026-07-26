<?php

use App\Modules\Core\Database\Seeders\DemoSeeder;
use App\Modules\Core\Enums\OrganizationRole;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\Role;
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
        fn (): array => array_values(
            $user->fresh()?->roles
                ->map(fn (Role $role): string => $role->name)
                ->sort()
                ->values()
                ->all() ?? []
        ),
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

    $this->assertDatabaseCount('users', 3);
    $this->assertDatabaseCount('organizations', 3);
    $this->assertDatabaseCount('organization_user', 5);

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

test('the demo seeder creates nothing in production', function () {
    app()->detectEnvironment(fn () => 'production');

    app(DemoSeeder::class)->run();

    // Known-password accounts have no place in a real deployment, so the seeder
    // is a no-op there rather than a foothold.
    expect(User::count())->toBe(0)
        ->and(Organization::count())->toBe(0);
});

test('the demo seeder can be run again after an account was closed', function () {
    app(DemoSeeder::class)->run();

    // A demo account closes their account (soft delete), then the seeder runs
    // again — a routine `migrate:fresh --seed` in development.
    User::firstWhere('email', 'asmaa@app.com')->delete();

    app(DemoSeeder::class)->run();

    // Brought back rather than colliding with the email it still reserved, and
    // no second copy left behind.
    expect(User::where('email', 'asmaa@app.com')->count())->toBe(1)
        ->and(User::withTrashed()->where('email', 'asmaa@app.com')->count())->toBe(1);
});

test('the demo seeder can be run again after an organization was removed', function () {
    app(DemoSeeder::class)->run();

    Organization::firstWhere('name', 'Nakheel')->delete();

    app(DemoSeeder::class)->run();

    // One organization named Nakheel, restored — not two, one trashed.
    expect(Organization::where('name', 'Nakheel')->count())->toBe(1)
        ->and(Organization::withTrashed()->where('name', 'Nakheel')->count())->toBe(1);
});
