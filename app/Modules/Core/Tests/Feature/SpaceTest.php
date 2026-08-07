<?php

use App\Modules\Core\Enums\OrganizationPermission;
use App\Modules\Core\Enums\OrganizationRole;
use App\Modules\Core\Enums\SpaceAccess;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\Space;
use App\Modules\Core\Models\User;
use App\Modules\Core\Services\OrganizationService;
use App\Modules\Core\Support\OrganizationContext;
use App\Modules\Core\Support\Spaces;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Spaces
|--------------------------------------------------------------------------
|
| Two questions kept deliberately apart: whether a space may be administered,
| which is a capability held through a role, and whether its contents may be
| reached, which is membership granted per space.
|
*/

test('an organization is created with a default space', function () {
    $owner = User::factory()->create();

    $organization = app(OrganizationService::class)->createForOwner($owner, 'Marketing');

    $spaces = OrganizationContext::for($organization, fn () => Space::all());

    expect($spaces)->toHaveCount(1)
        ->and($spaces->first()->is_default)->toBeTrue()
        ->and($spaces->first()->name)->toBe(__('core.spaces.default_name'));
});

test('provisioning the default space twice does not make a second one', function () {
    $organization = Organization::factory()->create();

    $first = Spaces::provisionDefault($organization);
    $second = Spaces::provisionDefault($organization);

    expect($second->is($first))->toBeTrue()
        ->and(OrganizationContext::for($organization, fn () => Space::count()))->toBe(1);
});

test('the database refuses a second default space', function () {
    $organization = Organization::factory()->create();

    Spaces::provisionDefault($organization);

    // Two defaults would make "the space everyone can reach" ambiguous, and
    // whatever resolved it would be picking one arbitrarily.
    expect(fn () => DB::transaction(
        fn () => OrganizationContext::for(
            $organization,
            fn () => Space::factory()->default()->create(),
        ),
    ))->toThrow(QueryException::class);
});

test('every member reaches the default space without being added to it', function () {
    $organization = Organization::factory()->create();
    $member = memberOf($organization);
    $default = Spaces::provisionDefault($organization);

    expect($default->accessFor($member))->toBe(SpaceAccess::Edit);
});

test('someone outside the organization reaches its default space through nothing', function () {
    $organization = Organization::factory()->create();
    $default = Spaces::provisionDefault($organization);

    expect($default->accessFor(User::factory()->create()))->toBeNull();
});

test('an ordinary space is reachable only by the people added to it', function () {
    $organization = Organization::factory()->create();
    $member = memberOf($organization);
    $stranger = memberOf($organization);

    $space = OrganizationContext::for($organization, fn () => Space::factory()->create());
    $space->members()->attach($member, ['access' => SpaceAccess::View->value]);

    expect($space->accessFor($member))->toBe(SpaceAccess::View)
        ->and($space->accessFor($stranger))->toBeNull();
});

test('view access opens a space but does not open it for editing', function () {
    $organization = Organization::factory()->create();
    $member = memberOf($organization);

    $space = OrganizationContext::for($organization, fn () => Space::factory()->create());
    $space->members()->attach($member, ['access' => SpaceAccess::View->value]);

    OrganizationContext::for($organization, function () use ($member, $space): void {
        expect($member->can('view', $space))->toBeTrue()
            ->and($member->can('edit', $space))->toBeFalse();
    });
});

test('edit access opens a space for editing', function () {
    $organization = Organization::factory()->create();
    $member = memberOf($organization);

    $space = OrganizationContext::for($organization, fn () => Space::factory()->create());
    $space->members()->attach($member, ['access' => SpaceAccess::Edit->value]);

    OrganizationContext::for($organization, function () use ($member, $space): void {
        expect($member->can('view', $space))->toBeTrue()
            ->and($member->can('edit', $space))->toBeTrue();
    });
});

test('a member who was never added reaches neither', function () {
    $organization = Organization::factory()->create();
    $member = memberOf($organization);

    $space = OrganizationContext::for($organization, fn () => Space::factory()->create());

    OrganizationContext::for($organization, function () use ($member, $space): void {
        expect($member->can('view', $space))->toBeFalse()
            ->and($member->can('edit', $space))->toBeFalse();
    });
});

test('administering spaces reaches every space without being a member of any', function () {
    $organization = Organization::factory()->create();
    $owner = memberOf($organization, OrganizationRole::Owner);

    $space = OrganizationContext::for($organization, fn () => Space::factory()->create());

    OrganizationContext::for($organization, function () use ($owner, $space): void {
        expect($owner->can(OrganizationPermission::ManageSpaces->value))->toBeTrue()
            ->and($owner->can('view', $space))->toBeTrue()
            ->and($owner->can('edit', $space))->toBeTrue()
            ->and($owner->can('create', Space::class))->toBeTrue()
            ->and($owner->can('manageMembers', $space))->toBeTrue();
    });
});

test('a member cannot administer spaces', function () {
    $organization = Organization::factory()->create();
    $member = memberOf($organization);

    $space = OrganizationContext::for($organization, fn () => Space::factory()->create());

    OrganizationContext::for($organization, function () use ($member, $space): void {
        expect($member->can('create', Space::class))->toBeFalse()
            ->and($member->can('update', $space))->toBeFalse()
            ->and($member->can('delete', $space))->toBeFalse()
            ->and($member->can('manageMembers', $space))->toBeFalse();
    });
});

test('the default space cannot be deleted, however much someone is allowed', function () {
    $organization = Organization::factory()->create();
    $owner = memberOf($organization, OrganizationRole::Owner);
    $default = Spaces::provisionDefault($organization);
    $ordinary = OrganizationContext::for($organization, fn () => Space::factory()->create());

    OrganizationContext::for($organization, function () use ($owner, $default, $ordinary): void {
        expect($owner->can('delete', $default))->toBeFalse()
            ->and($owner->can('delete', $ordinary))->toBeTrue();
    });
});

test('a space belongs to one organization and is invisible from another', function () {
    $mine = Organization::factory()->create();
    $theirs = Organization::factory()->create();

    OrganizationContext::for($mine, fn () => Space::factory()->create());

    expect(OrganizationContext::for($mine, fn () => Space::count()))->toBe(1)
        ->and(OrganizationContext::for($theirs, fn () => Space::count()))->toBe(0);
});

test('holding a role in one organization administers no space in another', function () {
    $mine = Organization::factory()->create();
    $theirs = Organization::factory()->create();

    $owner = memberOf($mine, OrganizationRole::Owner);
    $theirSpace = OrganizationContext::for($theirs, fn () => Space::factory()->create());

    // Acting inside the other organization, where this user holds nothing.
    OrganizationContext::for($theirs, function () use ($owner, $theirSpace): void {
        expect($owner->can('update', $theirSpace))->toBeFalse()
            ->and($owner->can('view', $theirSpace))->toBeFalse();
    });
});

test('spaces are ordered by the position the organization chose', function () {
    $organization = Organization::factory()->create();

    OrganizationContext::for($organization, function () use ($organization): void {
        Space::factory()->create(['name' => 'Second', 'position' => Spaces::nextPosition($organization)]);
        Space::factory()->create(['name' => 'Third', 'position' => Spaces::nextPosition($organization)]);
    });

    $names = OrganizationContext::for(
        $organization,
        fn () => Space::query()->orderBy('position')->pluck('name')->all(),
    );

    expect($names)->toBe(['Second', 'Third']);
});

test('the next position clears the highest in use rather than counting rows', function () {
    $organization = Organization::factory()->create();

    OrganizationContext::for($organization, fn () => Space::factory()->create(['position' => 7]));

    // Counting rows would hand the next space position 1, which is already free
    // but says nothing about where it should sit.
    expect(Spaces::nextPosition($organization))->toBe(8);
});

test('a space keeps its own activity timeline', function () {
    $organization = Organization::factory()->create();

    $space = OrganizationContext::for($organization, function (): Space {
        $space = Space::factory()->create(['name' => 'Campaigns']);
        $space->recordActivity('renamed', ['from' => 'Old', 'to' => 'Campaigns']);

        return $space;
    });

    expect($space->activities()->count())->toBe(1)
        ->and($space->activities->first()->type)->toBe('renamed');
});
