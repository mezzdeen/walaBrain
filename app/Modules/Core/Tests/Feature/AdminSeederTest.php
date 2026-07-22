<?php

use App\Modules\Core\Database\Seeders\AdminSeeder;
use App\Modules\Core\Database\Seeders\RolesAndPermissionsSeeder;
use App\Modules\Core\Enums\SuperRole;
use App\Modules\Core\Models\Admin;
use App\Modules\Core\Support\PermissionTeam;
use Illuminate\Support\Facades\Hash;

// The admin is given a role from the catalogue, so it has to exist first.
beforeEach(fn () => app(RolesAndPermissionsSeeder::class)->run());

test('the default admin is created with the configured password and role', function () {
    config([
        'core.admin.email' => 'boss@example.com',
        'core.admin.name' => 'Boss',
        'core.admin.password' => 'a-strong-secret',
    ]);

    app(AdminSeeder::class)->run();

    $admin = Admin::firstWhere('email', 'boss@example.com');

    expect($admin)->not->toBeNull()
        ->and(Hash::check('a-strong-secret', $admin->password))->toBeTrue();

    setPermissionsTeamId(PermissionTeam::SUPER);
    expect($admin->hasRole(SuperRole::SuperAdmin->value))->toBeTrue();
});

test('the admin reads its password from config, not a cached env default', function () {
    // What a cached configuration looks like: `env()` would return null here,
    // so a seeder still reaching for it would fall back to a known password.
    config(['core.admin.password' => 'set-by-the-operator']);

    app(AdminSeeder::class)->run();

    expect(Hash::check('set-by-the-operator', Admin::first()->password))->toBeTrue();
});

test('seeding the admin in production without a password fails loudly', function () {
    app()->detectEnvironment(fn () => 'production');
    config(['core.admin.password' => null]);

    expect(fn () => app(AdminSeeder::class)->run())->toThrow(RuntimeException::class);

    // Nothing rather than a back door: the run stops instead of creating an
    // admin whose password anyone reading the repository would know.
    expect(Admin::count())->toBe(0);
});

test('outside production the admin falls back to a throwaway password', function () {
    config(['core.admin.password' => null]);

    app(AdminSeeder::class)->run();

    expect(Admin::firstWhere('email', config('core.admin.email')))->not->toBeNull();
});

test('the admin seeder can be run again after the admin was removed', function () {
    app(AdminSeeder::class)->run();

    Admin::first()->delete();

    // Re-running restores the admin rather than hitting the unique email index.
    app(AdminSeeder::class)->run();

    expect(Admin::where('email', config('core.admin.email'))->count())->toBe(1)
        ->and(Admin::withTrashed()->where('email', config('core.admin.email'))->count())->toBe(1);
});
