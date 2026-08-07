<?php

use App\Modules\Core\Support\OrganizationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Required from tests/Pest.php, which is the only such file Pest boots. The
| module binds its own directory, so everything its tests need lives with the
| module and goes when the module does.
|
| The same reset as Core's: the organization context and the permission team
| are process-wide statics, so a test that leaves either set hands it to the
| next one and makes the order tests run in a hidden fixture.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function (): void {
        OrganizationContext::useGlobal();
        setPermissionsTeamId(null);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    })
    ->in(__DIR__);
