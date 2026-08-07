<?php

use App\Modules\Boards\Enums\FieldType;
use App\Modules\Boards\Models\Board;
use App\Modules\Boards\Models\Field;
use App\Modules\Boards\Models\Group;
use App\Modules\Boards\Models\Node;
use App\Modules\Core\Enums\OrganizationPermission;
use App\Modules\Core\Enums\OrganizationRole;
use App\Modules\Core\Enums\SpaceAccess;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Space;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\OrganizationContext;
use App\Modules\Core\Support\OrganizationRoles;

/*
|--------------------------------------------------------------------------
| Boards, Fields, Groups and Nodes
|--------------------------------------------------------------------------
|
| Where work lives. A board owns the fields its nodes carry and the groups
| they are displayed in; who can reach it is the space's answer, and what
| may be done to it is split between working and designing.
|
*/

/**
 * A board in a space, with the organization active for the callback.
 *
 * @param  callable(Board, Space): void  $callback
 */
function withBoard(Organization $organization, callable $callback): void
{
    OrganizationContext::for($organization, function () use ($callback): void {
        $space = Space::factory()->create();
        $callback(Board::factory()->in($space)->create(), $space);
    });
}

test('a board belongs to one organization and is invisible from another', function () {
    $mine = Organization::factory()->create();
    $theirs = Organization::factory()->create();

    withBoard($mine, fn () => null);

    expect(OrganizationContext::for($mine, fn () => Board::count()))->toBe(1)
        ->and(OrganizationContext::for($theirs, fn () => Board::count()))->toBe(0);
});

test('a board is described by fields of the ten canonical types', function () {
    $organization = Organization::factory()->create();

    withBoard($organization, function (Board $board): void {
        foreach (FieldType::cases() as $position => $type) {
            Field::factory()->on($board)->ofType($type)->create(['position' => $position]);
        }

        expect($board->fields()->count())->toBe(10)
            ->and($board->fields->pluck('type')->all())->toBe(FieldType::cases());
    });
});

test('the select-like types carry their options and the rest do not', function () {
    $organization = Organization::factory()->create();

    withBoard($organization, function (Board $board): void {
        $choice = Field::factory()->on($board)->ofType(FieldType::SingleSelect, ['pay', 'collect'])->create();
        $text = Field::factory()->on($board)->ofType(FieldType::Text)->create();

        expect($choice->options)->toBe(['pay', 'collect'])
            ->and($choice->type->hasOptions())->toBeTrue()
            ->and($text->options)->toBeNull()
            ->and($text->type->hasOptions())->toBeFalse();
    });
});

test('a node stores a board field\'s value under the field id, not its name', function () {
    $organization = Organization::factory()->create();

    withBoard($organization, function (Board $board): void {
        $amount = Field::factory()->on($board)->ofType(FieldType::Money)->create(['name' => 'Amount']);

        $node = Node::factory()->on($board)->create();
        $node->setValueFor($amount, 1500.5)->save();

        // Renaming must not orphan what was recorded under the old name.
        $amount->update(['name' => 'Total amount']);

        // The key is written as a string, because JSON object keys are strings,
        // but PHP turns a numeric string key back into an integer on the way
        // into an array. Lookup works either way; only array_keys shows it.
        expect($node->fresh()->valueFor($amount->fresh()))->toBe(1500.5)
            ->and(array_keys($node->fresh()->values))->toBe([$amount->id]);
    });
});

test('a money value is stored as a number so it can be sorted numerically', function () {
    $organization = Organization::factory()->create();

    withBoard($organization, function (Board $board): void {
        $amount = Field::factory()->on($board)->ofType(FieldType::Money)->create();

        foreach ([1200, 90, 30000] as $value) {
            Node::factory()->on($board)->create()->setValueFor($amount, $value)->save();
        }

        // The point of the storage contract: a formatted string would sort
        // "1,200" before "90" and nobody would notice until a report was wrong.
        $sorted = Node::query()
            ->orderByRaw('(values->>?)::numeric', [$amount->valueKey()])
            ->pluck('values');

        expect($sorted->map(fn (array $v): int|float => $v[$amount->valueKey()])->all())
            ->toBe([90, 1200, 30000]);
    });
});

test('a node carries the built-in attributes every node has', function () {
    $organization = Organization::factory()->create();

    withBoard($organization, function (Board $board) use ($organization): void {
        $assignee = memberOf($organization);
        $group = Group::factory()->on($board)->create();

        $node = Node::factory()->inGroup($group)->assignedTo($assignee, '2026-09-01')->create([
            'title' => 'Pay the invoice',
            'status' => 'in-review',
        ]);

        expect($node->title)->toBe('Pay the invoice')
            ->and($node->assignee->is($assignee))->toBeTrue()
            ->and($node->due_date->toDateString())->toBe('2026-09-01')
            ->and($node->status)->toBe('in-review')
            ->and($node->group->is($group))->toBeTrue()
            ->and($node->board->is($board))->toBeTrue();
    });
});

test('moving a node between groups changes nothing else about it', function () {
    $organization = Organization::factory()->create();

    withBoard($organization, function (Board $board): void {
        $field = Field::factory()->on($board)->create();
        $from = Group::factory()->on($board)->create();
        $to = Group::factory()->on($board)->create();

        $node = Node::factory()->inGroup($from)->create(['title' => 'Unchanged']);
        $node->setValueFor($field, 'kept')->save();

        $node->update(['group_id' => $to->getKey()]);

        expect($node->fresh()->group->is($to))->toBeTrue()
            ->and($node->fresh()->title)->toBe('Unchanged')
            ->and($node->fresh()->valueFor($field))->toBe('kept');
    });
});

test('my work gathers everything assigned to one person across boards, soonest first', function () {
    $organization = Organization::factory()->create();
    $me = memberOf($organization);
    $someoneElse = memberOf($organization);

    OrganizationContext::for($organization, function () use ($me, $someoneElse): void {
        $space = Space::factory()->create();
        $marketing = Board::factory()->in($space)->create();
        $finance = Board::factory()->in($space)->create();

        Node::factory()->on($finance)->assignedTo($me, '2026-09-10')->create(['title' => 'later']);
        Node::factory()->on($marketing)->assignedTo($me, '2026-09-01')->create(['title' => 'sooner']);
        Node::factory()->on($marketing)->assignedTo($me)->create(['title' => 'undated']);
        Node::factory()->on($marketing)->assignedTo($someoneElse, '2026-08-01')->create(['title' => 'not mine']);

        // One query over one table, because a task is a node however it came to
        // exist. An undated item is not more urgent than a dated one.
        expect(Node::query()->assignedTo($me)->pluck('title')->all())
            ->toBe(['sooner', 'later', 'undated']);
    });
});

test('reaching a board is the space\'s answer', function () {
    $organization = Organization::factory()->create();
    $member = memberOf($organization);

    withBoard($organization, function (Board $board, Space $space) use ($member): void {
        expect($member->can('view', $board))->toBeFalse();

        $space->members()->attach($member, ['access' => SpaceAccess::View->value]);

        expect($member->fresh()->can('view', $board->fresh()))->toBeTrue();
    });
});

test('working a board needs edit access; designing it needs the capability too', function () {
    $organization = Organization::factory()->create();
    $worker = memberOf($organization);

    withBoard($organization, function (Board $board, Space $space) use ($worker, $organization): void {
        $space->members()->attach($worker, ['access' => SpaceAccess::Edit->value]);

        $node = Node::factory()->on($board)->create();

        // Filling in requests all day should not carry the authority to
        // redefine the process while doing it.
        expect($worker->can('update', $node))->toBeTrue()
            ->and($worker->can('create', $node))->toBeTrue()
            ->and($worker->can('update', $board))->toBeFalse();

        $designer = memberOf($organization, OrganizationRole::Owner);
        $space->members()->attach($designer, ['access' => SpaceAccess::Edit->value]);

        expect($designer->can(OrganizationPermission::DesignProcesses->value))->toBeTrue()
            ->and($designer->can('update', $board))->toBeTrue();
    });
});

test('the capability without a space to use it in designs nothing', function () {
    $organization = Organization::factory()->create();

    // Deliberately not the owner role, which also carries spaces.manage and so
    // reaches every space anyway. This is somebody granted only the design
    // capability: authority with nowhere to exercise it.
    $designer = memberOf($organization);

    OrganizationRoles::within($organization, function () use ($designer): void {
        $designer->givePermissionTo(
            Permission::findOrCreate(OrganizationPermission::DesignProcesses->value, 'web'),
        );
    });

    withBoard($organization, function (Board $board) use ($designer): void {
        expect($designer->can(OrganizationPermission::DesignProcesses->value))->toBeTrue()
            ->and($designer->can(OrganizationPermission::ManageSpaces->value))->toBeFalse()
            ->and($designer->can('update', $board))->toBeFalse();
    });
});

test('an assignee reaches their own work wherever it lives', function () {
    $organization = Organization::factory()->create();
    $assignee = memberOf($organization);

    withBoard($organization, function (Board $board) use ($assignee): void {
        $node = Node::factory()->on($board)->assignedTo($assignee)->create();

        // Not a member of the space at all. Refusing somebody the item they
        // were told to act on would be the platform arguing with itself.
        expect($assignee->can('view', $node))->toBeTrue()
            ->and($assignee->can('update', $node))->toBeTrue()
            // Being asked to do something is not authority to erase it.
            ->and($assignee->can('delete', $node))->toBeFalse();
    });
});

test('the default board cannot be deleted', function () {
    $organization = Organization::factory()->create();
    $designer = memberOf($organization, OrganizationRole::Owner);

    OrganizationContext::for($organization, function () use ($designer): void {
        $space = Space::factory()->create();
        $space->members()->attach($designer, ['access' => SpaceAccess::Edit->value]);

        $default = Board::factory()->in($space)->default()->create();
        $ordinary = Board::factory()->in($space)->create();

        expect($designer->can('delete', $default))->toBeFalse()
            ->and($designer->can('delete', $ordinary))->toBeTrue();
    });
});

test('a node keeps an activity timeline', function () {
    $organization = Organization::factory()->create();

    withBoard($organization, function (Board $board): void {
        $node = Node::factory()->on($board)->create();
        $node->recordActivity('moved', ['to' => 'In review']);

        expect($node->activities()->count())->toBe(1)
            ->and($node->activities->first()->type)->toBe('moved');
    });
});

test('a stranger to the organization reaches none of it', function () {
    $organization = Organization::factory()->create();
    $stranger = User::factory()->create();

    withBoard($organization, function (Board $board) use ($stranger): void {
        $node = Node::factory()->on($board)->create();

        expect($stranger->can('view', $board))->toBeFalse()
            ->and($stranger->can('view', $node))->toBeFalse();
    });
});
