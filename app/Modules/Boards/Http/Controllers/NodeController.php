<?php

namespace App\Modules\Boards\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Boards\Models\Field;
use App\Modules\Boards\Models\Node;
use App\Modules\Core\Models\Activity;
use App\Modules\Core\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * One node in full: its values, its state, and everything that ever happened
 * to it.
 *
 * The page a reference number resolves to, a notification deep-links to, and
 * a submitter watches. When a request has been handed back for changes, this
 * is also where its submitter revises and resubmits it.
 */
class NodeController extends Controller
{
    /**
     * The node, its values, and its timeline.
     */
    public function show(Request $request, Node $node): Response
    {
        Gate::authorize('view', $node);

        /** @var User $user */
        $user = $request->user();

        $fields = $node->board->fields;

        return Inertia::render('nodes/show', [
            'node' => [
                'hash_id' => $node->hash_id,
                'title' => $node->title,
                'reference' => $node->reference,
                'status' => $node->status,
                'board' => $node->board->name,
                'assignee' => $node->assignee?->full_name,
                'submitter' => $node->creator?->full_name,
                'due_date' => $node->due_date?->toDateString(),
                'created_at' => $node->created_at?->toDateString(),
            ],
            'values' => $fields->map(fn (Field $field): array => [
                'hash_id' => $field->hash_id,
                'name' => $field->name,
                'type' => $field->type->value,
                'options' => $field->options,
                'is_required' => $field->is_required,
                'value' => $node->valueFor($field),
            ])->all(),

            // Whether the person looking is the submitter being asked to
            // revise — which turns the values above into an editable form.
            'can_resubmit' => $user->can('resubmit', $node),

            // Oldest first: a timeline reads down the page in the order things
            // happened, unlike a work list.
            'timeline' => $node->activities()
                ->with('actor')
                ->oldest('id')
                ->get()
                ->map(fn (Activity $activity): array => [
                    'type' => $activity->type,
                    'payload' => $activity->payload,
                    'actor' => $activity->actor?->getAttribute('full_name'),
                    'at' => $activity->created_at?->toDateTimeString(),
                ])->all(),
        ]);
    }
}
