<?php

namespace App\Modules\Boards\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Boards\Enums\FieldType;
use App\Modules\Boards\Models\Board;
use App\Modules\Boards\Models\Field;
use App\Modules\Boards\Models\Group;
use App\Modules\Boards\Models\Node;
use App\Modules\Forms\Models\Form;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The table a board is worked from: nodes as rows, fields as columns, any
 * column filterable and sortable.
 *
 * Filtering and sorting are display only — they change what is visible and in
 * what order, never what a node is. Both arrive as query parameters, so a
 * filtered view is a URL somebody can share.
 */
class BoardController extends Controller
{
    /**
     * The board as a filterable, sortable table.
     */
    public function show(Request $request, Board $board): Response
    {
        Gate::authorize('view', $board);

        $fields = $board->fields;
        // Node::query rather than the relation: the two filter helpers below
        // want the builder itself, and the relation only decorates one.
        $query = Node::query()
            ->where('board_id', $board->getKey())
            ->with(['assignee:id,first_name,last_name', 'group:id,name']);

        $this->applyFilters($query, $request, $fields);
        $this->applySort($query, $request, $fields);

        return Inertia::render('boards/show', [
            'board' => [
                'hash_id' => $board->hash_id,
                'name' => $board->name,
                'space' => $board->space->name,
            ],
            'fields' => $fields->map(fn (Field $field): array => [
                'hash_id' => $field->hash_id,
                'name' => $field->name,
                'type' => $field->type->value,
                'options' => $field->options,
            ])->all(),
            'groups' => $board->groups->map(fn (Group $group): array => [
                'hash_id' => $group->hash_id,
                'name' => $group->name,
            ])->all(),
            'nodes' => $query->get()->map(fn (Node $node): array => [
                'hash_id' => $node->hash_id,
                'title' => $node->title,
                'reference' => $node->reference,
                'status' => $node->status,
                'assignee' => $node->assignee?->full_name,
                'group' => $node->group?->name,
                'due_date' => $node->due_date?->toDateString(),
                'values' => collect($fields)->mapWithKeys(fn (Field $field): array => [
                    $field->hash_id => $node->valueFor($field),
                ])->all(),
            ])->all(),

            // Echoed back so the table renders its current view state.
            'sort' => (string) $request->query('sort', ''),
            'dir' => (string) $request->query('dir', 'asc'),
            'filters' => (array) $request->query('filter', []),

            // What can be submitted into this board. Announced by the Forms
            // module when it is installed; an empty list otherwise, which is
            // also the honest answer.
            'forms' => $this->formsFor($board),
        ]);
    }

    /**
     * Narrow the table to what the query string asks for.
     *
     * @param  Builder<Node>  $query
     * @param  Collection<int, Field>  $fields
     */
    private function applyFilters(Builder $query, Request $request, $fields): void
    {
        /** @var array<string, mixed> $filters */
        $filters = (array) $request->query('filter', []);

        foreach ($filters as $key => $value) {
            if (! is_string($value) || $value === '') {
                continue;
            }

            if ($key === 'status') {
                $query->where('status', $value);

                continue;
            }

            if ($key === 'title') {
                $query->where(function (Builder $builder) use ($value): void {
                    $builder->where('title', 'ilike', "%{$value}%")
                        ->orWhere('reference', 'ilike', "%{$value}%");
                });

                continue;
            }

            $field = $fields->first(fn (Field $field): bool => $field->hash_id === $key);

            if ($field === null) {
                continue;
            }

            // Selects match exactly; text matches anywhere. The jsonb text
            // operator serves both, which is what the storage contract buys.
            match ($field->type) {
                FieldType::SingleSelect, FieldType::Status => $query->whereRaw('(values->>?) = ?', [$field->valueKey(), $value]),
                FieldType::Text, FieldType::LongText => $query->whereRaw('(values->>?) ilike ?', [$field->valueKey(), "%{$value}%"]),
                default => null,
            };
        }
    }

    /**
     * Order the table by whichever column was asked for.
     *
     * @param  Builder<Node>  $query
     * @param  Collection<int, Field>  $fields
     */
    private function applySort(Builder $query, Request $request, $fields): void
    {
        $key = (string) $request->query('sort', '');
        $direction = $request->query('dir') === 'desc' ? 'desc' : 'asc';

        $builtIn = ['title', 'reference', 'status', 'due_date', 'created_at'];

        if (in_array($key, $builtIn, true)) {
            $query->orderBy($key, $direction)->orderBy('id');

            return;
        }

        $field = $fields->first(fn (Field $field): bool => $field->hash_id === $key);

        if ($field !== null) {
            // Numeric types cast so 90 sorts before 1200; everything else
            // sorts as text, which for ISO dates is also chronological.
            match ($field->type) {
                FieldType::Number, FieldType::Money => $query->orderByRaw(
                    "(values->>?)::numeric {$direction} nulls last",
                    [$field->valueKey()],
                ),
                default => $query->orderByRaw(
                    "(values->>?) {$direction} nulls last",
                    [$field->valueKey()],
                ),
            };

            $query->orderBy('id');

            return;
        }

        $query->latest('id');
    }

    /**
     * The published forms that create nodes here, where Forms is installed.
     *
     * A soft reach across modules, written out as one: Boards works without
     * Forms, and the class check is the whole of the coupling.
     *
     * @return list<array{hash_id: string, name: string}>
     */
    private function formsFor(Board $board): array
    {
        if (! class_exists(Form::class)) {
            return [];
        }

        $options = [];

        foreach (Form::query()->where('board_id', $board->getKey())->whereNotNull('published_at')->get() as $form) {
            $options[] = [
                'hash_id' => (string) $form->hash_id,
                'name' => $form->name,
            ];
        }

        return $options;
    }
}
