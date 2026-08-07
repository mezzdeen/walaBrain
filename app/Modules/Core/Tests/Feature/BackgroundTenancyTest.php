<?php

use App\Modules\Core\Models\Activity;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Support\OrganizationContext;
use App\Modules\Core\Support\Organizations;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Background Tenancy
|--------------------------------------------------------------------------
|
| Middleware sets the active organization for a web request; a queued job
| runs long after that request is gone. These drive the real path — payload
| serialised to the database, popped by a worker, deserialised — rather than
| the hooks in isolation, because it is the round trip that has to hold.
|
*/

/** Records which organization it found itself running as. */
class ObservesTenancy implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var array<string, int|null> */
    public static array $seen = [];

    public function __construct(public string $label) {}

    public function handle(): void
    {
        self::$seen[$this->label] = OrganizationContext::current()?->getKey();
    }
}

/** Fails, so the clean-up after a failure can be checked too. */
class FailsLoudly implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        throw new RuntimeException('boom');
    }
}

/**
 * Drain the queue in this process, so the worker shares the test's transaction.
 */
function drainTheQueue(): void
{
    // Not `--once`, which stops after a single job: several of these need one
    // worker to serve two jobs in a row, because that is where a leak would
    // show.
    Artisan::call('queue:work', [
        '--stop-when-empty' => true,
        '--tries' => 1,
    ]);
}

beforeEach(function (): void {
    // The database connection, not `sync`: sync never serialises a payload, so
    // it would prove nothing about the round trip these tests exist for.
    config(['queue.default' => 'database']);

    ObservesTenancy::$seen = [];
});

test('a job runs as the organization it was dispatched from', function () {
    $organization = Organization::factory()->create();

    OrganizationContext::for($organization, function (): void {
        ObservesTenancy::dispatch('only');
    });

    drainTheQueue();

    expect(ObservesTenancy::$seen['only'])->toBe($organization->getKey());
});

test('a job can write to tenant-owned records without naming the organization', function () {
    $organization = Organization::factory()->create();

    OrganizationContext::for($organization, function (): void {
        RecordsSomething::dispatch();
    });

    drainTheQueue();

    // The write would have thrown had the context not been restored, since
    // an activity with no organization belongs to no tenant.
    $activities = OrganizationContext::for($organization, fn () => Activity::all());

    expect($activities)->toHaveCount(1)
        ->and($activities->first()->type)->toBe('job-ran');
});

test('one job does not inherit the organization of the job before it', function () {
    $first = Organization::factory()->create();
    $second = Organization::factory()->create();

    OrganizationContext::for($first, function (): void {
        ObservesTenancy::dispatch('first');
    });
    OrganizationContext::for($second, function (): void {
        ObservesTenancy::dispatch('second');
    });

    // One worker serves both, in one process. This is the leak the whole
    // arrangement exists to prevent.
    drainTheQueue();

    expect(ObservesTenancy::$seen['first'])->toBe($first->getKey())
        ->and(ObservesTenancy::$seen['second'])->toBe($second->getKey());
});

test('a job dispatched by something belonging to no tenant runs unscoped', function () {
    // No context at all, as a console command or a scheduled sweep would have.
    OrganizationContext::useGlobal();

    ObservesTenancy::dispatch('console');

    drainTheQueue();

    expect(ObservesTenancy::$seen['console'])->toBeNull();
});

test('a failed job does not leave its organization set for the next one', function () {
    $organization = Organization::factory()->create();

    OrganizationContext::for($organization, function (): void {
        FailsLoudly::dispatch();
    });
    OrganizationContext::useGlobal();
    ObservesTenancy::dispatch('after-the-failure');

    drainTheQueue();

    expect(ObservesTenancy::$seen['after-the-failure'])->toBeNull();
});

test('a job whose organization was deleted meanwhile is confined to nothing', function () {
    $organization = Organization::factory()->create();

    OrganizationContext::for($organization, function (): void {
        ObservesTenancy::dispatch('orphaned');
    });

    $organization->forceDelete();

    drainTheQueue();

    // Not "acts across every tenant" — a tenant that no longer exists confines
    // the job to nothing, which is the safe reading.
    expect(ObservesTenancy::$seen)->toHaveKey('orphaned')
        ->and(ObservesTenancy::$seen['orphaned'])->toBeNull();
});

test('returning a pending dispatch from a scoped block loses the organization', function () {
    $organization = Organization::factory()->create();

    // Documenting a trap rather than asserting a wish. `Job::dispatch()` returns
    // a PendingDispatch that pushes the job when it is destroyed, so an arrow
    // function hands it back out of the block and it is pushed after the context
    // has already been restored. The block-bodied closure above is the fix; this
    // is here so nobody "tidies" one into the other and quietly breaks tenancy.
    OrganizationContext::for($organization, fn () => ObservesTenancy::dispatch('escaped'));

    drainTheQueue();

    expect(ObservesTenancy::$seen['escaped'])->toBeNull();
});

test('scheduled work runs once per organization, as that organization', function () {
    $first = Organization::factory()->create();
    $second = Organization::factory()->create();

    $seen = [];

    Organizations::each(function (Organization $organization) use (&$seen): void {
        $seen[$organization->getKey()] = OrganizationContext::current()?->getKey();
    });

    expect($seen)->toBe([
        $first->getKey() => $first->getKey(),
        $second->getKey() => $second->getKey(),
    ]);
});

test('scheduled work leaves the context as it found it', function () {
    Organization::factory()->create();

    OrganizationContext::useGlobal();

    Organizations::each(fn () => null);

    expect(OrganizationContext::current())->toBeNull()
        ->and(OrganizationContext::isScoped())->toBeFalse();
});

test('one organization throwing does not leave the next running as it', function () {
    $first = Organization::factory()->create();
    $second = Organization::factory()->create();

    $seen = [];

    // A block closure, not an arrow function: an arrow function captures by
    // value, so the inner `&$seen` would bind to its copy and nothing would
    // reach the assertion below.
    $sweep = function () use (&$seen, $first): void {
        Organizations::each(function (Organization $organization) use (&$seen, $first): void {
            $seen[$organization->getKey()] = OrganizationContext::current()?->getKey();

            if ($organization->is($first)) {
                throw new RuntimeException('boom');
            }
        });
    };

    expect($sweep)->toThrow(RuntimeException::class);

    // The sweep stopped at the failure, and did not carry its organization out.
    expect($seen)->toBe([$first->getKey() => $first->getKey()])
        ->and(OrganizationContext::current())->toBeNull();
});

/** Writes a tenant-owned record, which is impossible without a context. */
class RecordsSomething implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        OrganizationContext::current()?->recordActivity('job-ran');
    }
}
