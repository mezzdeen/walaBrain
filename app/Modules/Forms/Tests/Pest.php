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
| Required from tests/Pest.php, the only such file Pest boots. The same
| reset as every other module: the organization context and permission team
| are process-wide statics, and a test that leaves either set hands it to
| whichever test runs next.
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
