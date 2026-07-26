<?php

use App\Modules\Core\Concerns\BelongsToOrganization;
use App\Modules\Core\Exceptions\MissingOrganizationContext;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Support\OrganizationContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * The trait is infrastructure with no adopter yet — no domain table exists, and
 * the two that carry an `organization_id` today opt out for reasons documented
 * on the trait. Testing it against a table made here is the honest option: an
 * invented production model would be dead weight the moment a real one lands.
 *
 * @property int $id
 * @property int $organization_id
 * @property string $title
 */
class ScopedRecord extends Model
{
    use BelongsToOrganization;

    protected $table = 'scoped_records';

    protected $guarded = [];
}

beforeEach(function () {
    Schema::create('scoped_records', function ($table) {
        $table->id();
        $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
        $table->string('title');
        $table->timestamps();
    });

    // The console starts unscoped, which is what lets seeders and commands work
    // as they always have. Most tests here want the request-time behaviour.
    OrganizationContext::clear();
});

test('a record is stamped with the active organization on create', function () {
    $organization = Organization::factory()->create();

    OrganizationContext::use($organization);

    $record = ScopedRecord::create(['title' => 'A report']);

    expect($record->organization_id)->toBe($organization->getKey());
});

test('an organization set by hand is not overwritten on create', function () {
    [$active, $other] = Organization::factory()->count(2)->create()->all();

    OrganizationContext::use($active);

    $record = ScopedRecord::create([
        'title' => 'A report',
        'organization_id' => $other->getKey(),
    ]);

    expect($record->organization_id)->toBe($other->getKey());
});

test('creating without an organization fails loudly', function () {
    OrganizationContext::use(null);

    ScopedRecord::create(['title' => 'An orphan']);
})->throws(MissingOrganizationContext::class);

test('a query only returns rows of the active organization', function () {
    [$mine, $theirs] = Organization::factory()->count(2)->create()->all();

    ScopedRecord::create(['title' => 'Mine', 'organization_id' => $mine->getKey()]);
    ScopedRecord::create(['title' => 'Theirs', 'organization_id' => $theirs->getKey()]);

    OrganizationContext::use($mine);

    expect(ScopedRecord::pluck('title')->all())->toBe(['Mine']);
});

test('a query fails loudly when the request is scoped to no organization', function () {
    OrganizationContext::use(null);

    ScopedRecord::count();
})->throws(MissingOrganizationContext::class);

test('the escape hatch reads every organization', function () {
    [$mine, $theirs] = Organization::factory()->count(2)->create()->all();

    ScopedRecord::create(['title' => 'Mine', 'organization_id' => $mine->getKey()]);
    ScopedRecord::create(['title' => 'Theirs', 'organization_id' => $theirs->getKey()]);

    OrganizationContext::use($mine);

    expect(ScopedRecord::allOrganizations()->count())->toBe(2);
});

test('the console reads every organization by default', function () {
    [$mine, $theirs] = Organization::factory()->count(2)->create()->all();

    ScopedRecord::create(['title' => 'Mine', 'organization_id' => $mine->getKey()]);
    ScopedRecord::create(['title' => 'Theirs', 'organization_id' => $theirs->getKey()]);

    // Nothing named an organization, so nothing narrowed the world. A seeder
    // that could only ever see one tenant's rows would be useless.
    expect(ScopedRecord::count())->toBe(2);
});

test('withoutScoping lifts the filter and then restores it', function () {
    [$mine, $theirs] = Organization::factory()->count(2)->create()->all();

    ScopedRecord::create(['title' => 'Mine', 'organization_id' => $mine->getKey()]);
    ScopedRecord::create(['title' => 'Theirs', 'organization_id' => $theirs->getKey()]);

    OrganizationContext::use($mine);

    $all = OrganizationContext::withoutScoping(fn (): int => ScopedRecord::count());

    expect($all)->toBe(2)
        ->and(ScopedRecord::count())->toBe(1)
        ->and(OrganizationContext::current()?->getKey())->toBe($mine->getKey());
});

test('for() moves the organization and the permission team together', function () {
    [$first, $second] = Organization::factory()->count(2)->create()->all();

    OrganizationContext::use($first);

    OrganizationContext::for($second, function () use ($second) {
        // Both have to move, or a role check inside the callback would answer
        // for one organization while the query reads from another.
        expect(OrganizationContext::current()?->getKey())->toBe($second->getKey())
            ->and(getPermissionsTeamId())->toBe($second->getKey());
    });

    expect(OrganizationContext::current()?->getKey())->toBe($first->getKey())
        ->and(getPermissionsTeamId())->toBe($first->getKey());
});

test('for() restores an unscoped context rather than confining it', function () {
    $organization = Organization::factory()->create();

    OrganizationContext::for($organization, fn () => null);

    // A job that borrowed a tenant must hand the process back as it found it,
    // or everything after it silently reads one organization's rows.
    expect(OrganizationContext::isScoped())->toBeFalse();
});

test('the admin platform is not confined to any organization', function () {
    $admin = superAdmin();
    $organization = Organization::factory()->create();

    ScopedRecord::create(['title' => 'Theirs', 'organization_id' => $organization->getKey()]);

    $this->actingAs($admin, 'super')->get(route('super.organizations.index'))->assertOk();

    expect(OrganizationContext::isScoped())->toBeFalse()
        ->and(ScopedRecord::count())->toBe(1);
});
