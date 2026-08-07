<?php

use App\Modules\Boards\Models\Board;
use App\Modules\Boards\Models\Node;
use App\Modules\Boards\Support\Boards;
use App\Modules\Core\Enums\OrganizationPermission;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\User;
use App\Modules\Core\Services\OrganizationService;
use App\Modules\Core\Support\OrganizationContext;
use App\Modules\Core\Support\OrganizationMembers;
use App\Modules\Core\Support\OrganizationRoles;
use App\Modules\Core\Support\Spaces;

/*
|--------------------------------------------------------------------------
| My Work
|--------------------------------------------------------------------------
|
| The screen somebody opens first. A task is a node however it came to exist,
| so this is one query rather than a union, and the interesting rules are
| about who may hand work to whom.
|
*/

/**
 * A member of the organization, with that organization made the active one.
 *
 * Signing in is left to the caller: `$this` is the test case there, and a
 * helper function has no access to it.
 */
function workingIn(Organization $organization, ?User $user = null): User
{
    $user ??= memberOf($organization);

    OrganizationContext::switch($organization);

    return $user;
}

test('a new organization is given the board its loose work lives on', function () {
    $owner = User::factory()->create();

    $organization = app(OrganizationService::class)->createForOwner($owner, 'Marketing');

    $board = OrganizationContext::for($organization, fn () => Board::query()->where('is_default', true)->first());

    expect($board)->not->toBeNull()
        ->and($board->name)->toBe(__('boards::boards.default_name'))
        // In the default space, which every member reaches without being added.
        ->and($board->space->is_default)->toBeTrue();
});

test('provisioning the default board twice does not make a second one', function () {
    $organization = Organization::factory()->create();
    Spaces::provisionDefault($organization);

    $first = Boards::provisionDefault($organization);
    $second = Boards::provisionDefault($organization);

    expect($second->is($first))->toBeTrue()
        ->and(OrganizationContext::for($organization, fn () => Board::count()))->toBe(1);
});

test('my work shows what is open and assigned, soonest first', function () {
    $organization = Organization::factory()->create();
    $me = workingIn($organization);
    $this->actingAs($me);

    OrganizationContext::for($organization, function () use ($organization, $me): void {
        Spaces::provisionDefault($organization);
        $board = Boards::provisionDefault($organization);

        Node::factory()->on($board)->assignedTo($me, '2026-09-10')->create(['title' => 'later']);
        Node::factory()->on($board)->assignedTo($me, '2026-09-01')->create(['title' => 'sooner']);
        Node::factory()->on($board)->assignedTo($me)->create([
            'title' => 'already done',
            'completed_at' => now(),
        ]);
    });

    $this->get(route('my-work.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('my-work')
            ->has('tasks', 2)
            ->where('tasks.0.title', 'sooner')
            ->where('tasks.1.title', 'later'));
});

test('a task written for yourself lands on the default board', function () {
    $organization = Organization::factory()->create();
    Spaces::provisionDefault($organization);
    Boards::provisionDefault($organization);

    $me = workingIn($organization);
    $this->actingAs($me);

    $this->post(route('my-work.store'), [
        'title' => 'Call the vendor',
        'due_date' => '2026-09-01',
    ])->assertRedirect();

    $node = OrganizationContext::for($organization, fn () => Node::query()->firstOrFail());

    expect($node->title)->toBe('Call the vendor')
        ->and($node->assignee_id)->toBe($me->getKey())
        ->and($node->board->is_default)->toBeTrue()
        ->and($node->activities()->count())->toBe(1);
});

test('a manager can write a task for someone who reports to them', function () {
    $organization = Organization::factory()->create();
    Spaces::provisionDefault($organization);
    Boards::provisionDefault($organization);

    $report = memberOf($organization);
    $manager = workingIn($organization);
    $this->actingAs($manager);

    OrganizationMembers::setManager($organization, $report, $manager);

    $this->post(route('my-work.store'), [
        'title' => 'Follow up after the meeting',
        'assignee' => $report->hash_id,
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect(OrganizationContext::for($organization, fn () => Node::query()->firstOrFail())->assignee_id)
        ->toBe($report->getKey());
});

test('work cannot be pushed onto a colleague who does not report to you', function () {
    $organization = Organization::factory()->create();
    Spaces::provisionDefault($organization);
    Boards::provisionDefault($organization);

    $colleague = memberOf($organization);
    $this->actingAs(workingIn($organization));

    // Knowing somebody's code is not authority to give them work.
    $this->post(route('my-work.store'), [
        'title' => 'Do this for me',
        'assignee' => $colleague->hash_id,
    ])->assertSessionHasErrors('assignee');

    expect(OrganizationContext::for($organization, fn () => Node::count()))->toBe(0);
});

test('the assignment capability allows handing work to anyone in the business line', function () {
    $organization = Organization::factory()->create();
    Spaces::provisionDefault($organization);
    Boards::provisionDefault($organization);

    $colleague = memberOf($organization);
    $router = memberOf($organization);

    OrganizationRoles::within($organization, function () use ($router): void {
        $router->givePermissionTo(
            Permission::findOrCreate(OrganizationPermission::AssignTasks->value, 'web'),
        );
    });

    $this->actingAs(workingIn($organization, $router));

    $this->post(route('my-work.store'), [
        'title' => 'Route this',
        'assignee' => $colleague->hash_id,
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect(OrganizationContext::for($organization, fn () => Node::query()->firstOrFail())->assignee_id)
        ->toBe($colleague->getKey());
});

test('somebody from another business line cannot be assigned to at all', function () {
    $organization = Organization::factory()->create();
    $elsewhere = Organization::factory()->create();

    Spaces::provisionDefault($organization);
    Boards::provisionDefault($organization);

    $outsider = memberOf($elsewhere);
    $router = memberOf($organization);

    OrganizationRoles::within($organization, function () use ($router): void {
        $router->givePermissionTo(
            Permission::findOrCreate(OrganizationPermission::AssignTasks->value, 'web'),
        );
    });

    $this->actingAs(workingIn($organization, $router));

    // Even holding the capability: the code names a real account, but not one
    // in this business line.
    $this->post(route('my-work.store'), [
        'title' => 'Cross the boundary',
        'assignee' => $outsider->hash_id,
    ])->assertSessionHasErrors('assignee');
});

test('marking a task done takes it off the list', function () {
    $organization = Organization::factory()->create();
    Spaces::provisionDefault($organization);
    Boards::provisionDefault($organization);

    $me = workingIn($organization);
    $this->actingAs($me);

    $node = OrganizationContext::for($organization, function () use ($me): Node {
        $board = Board::query()->where('is_default', true)->firstOrFail();

        return Node::factory()->on($board)->assignedTo($me)->create(['title' => 'Do it']);
    });

    $this->patch(route('my-work.complete', $node->hash_id))->assertRedirect();

    expect($node->fresh()->completed_at)->not->toBeNull();

    $this->get(route('my-work.index'))
        ->assertInertia(fn ($page) => $page->has('tasks', 0));
});

test('completing an already completed task is not an error', function () {
    $organization = Organization::factory()->create();
    Spaces::provisionDefault($organization);
    Boards::provisionDefault($organization);

    $me = workingIn($organization);
    $this->actingAs($me);

    $node = OrganizationContext::for($organization, function () use ($me): Node {
        $board = Board::query()->where('is_default', true)->firstOrFail();

        return Node::factory()->on($board)->assignedTo($me)->create();
    });

    $this->patch(route('my-work.complete', $node->hash_id))->assertRedirect();
    $completedAt = $node->fresh()->completed_at;

    // Two clicks on a slow connection should not produce two answers.
    $this->patch(route('my-work.complete', $node->hash_id))->assertRedirect();

    expect($node->fresh()->completed_at->eq($completedAt))->toBeTrue()
        ->and($node->activities()->where('type', 'task.completed')->count())->toBe(1);
});

test('somebody who belongs to no business line is sent away rather than erroring', function () {
    $organization = Organization::factory()->create();
    Spaces::provisionDefault($organization);
    Boards::provisionDefault($organization);

    $owner = memberOf($organization);

    $node = OrganizationContext::for($organization, function () use ($owner): Node {
        $board = Board::query()->where('is_default', true)->firstOrFail();

        return Node::factory()->on($board)->assignedTo($owner)->create();
    });

    // Resolving the binding runs a scoped query, which throws outright with no
    // organization set — so without the middleware ordering this is a 500 from
    // the binding rather than a redirect from the check.
    $this->actingAs(User::factory()->create())
        ->patch(route('my-work.complete', $node->hash_id))
        ->assertRedirect(route('organizations.none'));

    expect($node->fresh()->completed_at)->toBeNull();
});

test('a colleague on the same shared board may help with the work on it', function () {
    $organization = Organization::factory()->create();
    Spaces::provisionDefault($organization);
    Boards::provisionDefault($organization);

    $owner = memberOf($organization);

    $node = OrganizationContext::for($organization, function () use ($owner): Node {
        $board = Board::query()->where('is_default', true)->firstOrFail();

        return Node::factory()->on($board)->assignedTo($owner)->create();
    });

    // Deliberate, not an oversight: the default board sits in the default
    // space, which every member of the business line can edit. Shared work on a
    // shared board is shared. A board in a space somebody was never added to is
    // what refuses them, and that is covered in BoardTest.
    $this->actingAs(workingIn($organization));

    $this->patch(route('my-work.complete', $node->hash_id))->assertRedirect();

    expect($node->fresh()->completed_at)->not->toBeNull();
});

test('a task from another business line is not reachable', function () {
    $organization = Organization::factory()->create();
    $elsewhere = Organization::factory()->create();

    foreach ([$organization, $elsewhere] as $each) {
        Spaces::provisionDefault($each);
        Boards::provisionDefault($each);
    }

    $me = memberOf($organization);
    $elsewhere->users()->attach($me);

    $theirNode = OrganizationContext::for($elsewhere, function () use ($me): Node {
        $board = Board::query()->where('is_default', true)->firstOrFail();

        return Node::factory()->on($board)->assignedTo($me)->create();
    });

    // Their own task, but they are working in the other business line.
    $this->actingAs(workingIn($organization, $me));

    $this->patch(route('my-work.complete', $theirNode->hash_id))->assertNotFound();
});
