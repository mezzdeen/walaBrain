<?php

use App\Modules\Core\Exceptions\ActivityIsAppendOnly;
use App\Modules\Core\Exceptions\MissingOrganizationContext;
use App\Modules\Core\Models\Activity;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\OrganizationContext;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Activity Timeline
|--------------------------------------------------------------------------
|
| The application's memory. Everything else in the platform is allowed to be
| wrong and corrected; this is the one table where a correction is a new row
| and the old one stays exactly as it was written.
|
*/

test('an entry is recorded against its subject', function () {
    $organization = Organization::factory()->create();

    $activity = OrganizationContext::for(
        $organization,
        fn () => $organization->recordActivity('renamed', ['from' => 'Old', 'to' => 'New']),
    );

    expect($activity->subject_type)->toBe($organization->getMorphClass())
        ->and($activity->subject_id)->toBe($organization->getKey())
        ->and($activity->type)->toBe('renamed')
        ->and($activity->payload)->toBe(['from' => 'Old', 'to' => 'New'])
        ->and($organization->activities()->count())->toBe(1);
});

test('an empty payload is stored as nothing rather than an empty object', function () {
    $organization = Organization::factory()->create();

    $activity = OrganizationContext::for(
        $organization,
        fn () => $organization->recordActivity('created'),
    );

    expect($activity->fresh()->payload)->toBeNull();
});

test('the timeline reads most recent first', function () {
    $organization = Organization::factory()->create();

    OrganizationContext::for($organization, function () use ($organization): void {
        $organization->recordActivity('created');
        $organization->recordActivity('renamed');
        $organization->recordActivity('recoloured');
    });

    expect($organization->activities->pluck('type')->all())
        ->toBe(['recoloured', 'renamed', 'created']);
});

test('the signed-in user is credited without being named', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user);

    $activity = OrganizationContext::for(
        $organization,
        fn () => $organization->recordActivity('renamed'),
    );

    expect($activity->actor_type)->toBe($user->getMorphClass())
        ->and($activity->actor_id)->toBe($user->getKey())
        ->and($activity->actor->is($user))->toBeTrue();
});

test('the admin platform credits the admin, not a user signed in alongside them', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create();
    $admin = superAdmin();

    // A browser can hold a session on both platforms at once. The path decides
    // which identity acted, so a user signed in on the company platform is not
    // credited for something done from the admin platform.
    $this->actingAs($user);
    Auth::guard('super')->setUser($admin);
    app()->instance('request', Request::create('/super/organizations'));

    $activity = OrganizationContext::for(
        $organization,
        fn () => $organization->recordActivity('created'),
    );

    expect($activity->actor_type)->toBe($admin->getMorphClass())
        ->and($activity->actor_id)->toBe($admin->getKey());
});

test('work nobody triggered is recorded with no actor', function () {
    $organization = Organization::factory()->create();

    $this->actingAs(User::factory()->create());

    $activity = OrganizationContext::for(
        $organization,
        // Explicitly null: the system did this, overriding the signed-in user.
        fn () => $organization->recordActivity('expired', [], null),
    );

    expect($activity->actor_type)->toBeNull()
        ->and($activity->actor_id)->toBeNull()
        ->and($activity->actor)->toBeNull();
});

test('an entry cannot be updated through the model', function () {
    $organization = Organization::factory()->create();

    $activity = OrganizationContext::for(
        $organization,
        fn () => $organization->recordActivity('renamed'),
    );

    expect(fn () => $activity->update(['type' => 'tampered']))
        ->toThrow(ActivityIsAppendOnly::class);
});

test('an entry cannot be deleted through the model', function () {
    $organization = Organization::factory()->create();

    $activity = OrganizationContext::for(
        $organization,
        fn () => $organization->recordActivity('renamed'),
    );

    expect(fn () => $activity->delete())
        ->toThrow(ActivityIsAppendOnly::class);
});

test('the database refuses an update that goes around the model', function () {
    $organization = Organization::factory()->create();

    $activity = OrganizationContext::for(
        $organization,
        fn () => $organization->recordActivity('renamed'),
    );

    // Wrapped so the failed statement rolls back to a savepoint: Postgres
    // aborts the whole transaction otherwise, and the surrounding test's own
    // transaction would be unusable afterwards.
    expect(fn () => DB::transaction(
        fn () => DB::table('activities')->where('id', $activity->id)->update(['type' => 'tampered']),
    ))->toThrow(QueryException::class);

    expect($activity->fresh()->type)->toBe('renamed');
});

test('the database refuses a delete that goes around the model', function () {
    $organization = Organization::factory()->create();

    $activity = OrganizationContext::for(
        $organization,
        fn () => $organization->recordActivity('renamed'),
    );

    expect(fn () => DB::transaction(
        fn () => DB::table('activities')->where('id', $activity->id)->delete(),
    ))->toThrow(QueryException::class);

    expect($activity->fresh())->not->toBeNull();
});

test('an entry belongs to one organization and is invisible from another', function () {
    $mine = Organization::factory()->create();
    $theirs = Organization::factory()->create();

    OrganizationContext::for($mine, fn () => $mine->recordActivity('renamed'));

    expect(OrganizationContext::for($mine, fn () => Activity::count()))->toBe(1)
        ->and(OrganizationContext::for($theirs, fn () => Activity::count()))->toBe(0);
});

test('an entry cannot be recorded without knowing which organization it belongs to', function () {
    $organization = Organization::factory()->create();

    // No context at all: a row with no organization belongs to no tenant and is
    // visible to none, which is corruption rather than a missing filter.
    OrganizationContext::use(null);

    expect(fn () => $organization->recordActivity('renamed'))
        ->toThrow(MissingOrganizationContext::class);
});
