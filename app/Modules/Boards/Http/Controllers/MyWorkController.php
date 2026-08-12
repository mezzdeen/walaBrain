<?php

namespace App\Modules\Boards\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Boards\Http\Requests\StoreTaskRequest;
use App\Modules\Boards\Models\Board;
use App\Modules\Boards\Models\Node;
use App\Modules\Boards\Support\Boards;
use App\Modules\Core\Enums\OrganizationPermission;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\MyWorkSources;
use App\Modules\Core\Support\OrganizationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Everything waiting on one person, wherever it came from.
 *
 * The screen somebody opens first, rather than a board: a person's daily
 * question is "what do I need to do", and the answer should not depend on
 * remembering which board each piece of work lives on. One query over nodes
 * answers it, because a task is a node however it was created.
 */
class MyWorkController extends Controller
{
    /**
     * What is still open and assigned to the person, soonest first.
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $nodes = Node::query()
            ->assignedTo($user)
            ->open()
            ->with(['board:id,name'])
            ->get(['id', 'board_id', 'title', 'description', 'due_date', 'created_at']);

        return Inertia::render('my-work', [
            'tasks' => $nodes->map(fn (Node $node): array => [
                'hash_id' => $node->hash_id,
                'title' => $node->title,
                'description' => $node->description,
                'due_date' => $node->due_date?->toDateString(),
                'is_overdue' => $node->isOverdue(),
                'board' => $node->board->name,
            ])->all(),

            // Who this person may hand work to. Themselves always; their
            // reports because they manage them; everybody in the business line
            // if they hold the capability for routing work generally.
            'assignable' => $this->assignableTo($user),

            // What other modules have waiting on this person — approvals, once
            // Flows is installed — without this module naming any of them.
            ...MyWorkSources::collect($user),
        ]);
    }

    /**
     * Write a task by hand.
     *
     * It lands on the organization's default board, which is where work that
     * belongs to no particular process lives.
     */
    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $organization = OrganizationContext::current();
        $assignee = $request->resolveAssignee();

        $board = Board::query()->where('is_default', true)->first()
            ?? ($organization !== null ? Boards::provisionDefault($organization) : null);

        abort_if($board === null || $assignee === null, 404);

        $node = new Node([
            'board_id' => $board->getKey(),
            'title' => $request->validated('title'),
            'description' => $request->validated('description'),
            'due_date' => $request->validated('due_date'),
            'assignee_id' => $assignee->getKey(),
        ]);

        Gate::authorize('create', $node);

        $node->save();

        $node->recordActivity('task.created', [
            'title' => $node->title,
            'assignee' => $assignee->hash_id,
        ]);

        return back();
    }

    /**
     * Mark a task done, so it leaves the person's list.
     */
    public function complete(Request $request, Node $node): RedirectResponse
    {
        Gate::authorize('update', $node);

        // Already done is not an error: two clicks on a slow connection should
        // not produce two different answers.
        if ($node->completed_at === null) {
            $node->forceFill(['completed_at' => now()])->save();

            $node->recordActivity('task.completed');
        }

        return back();
    }

    /**
     * Everyone this person may assign work to, as the form's options.
     *
     * @return list<array{hash_id: string, name: string}>
     */
    private function assignableTo(User $user): array
    {
        $organization = OrganizationContext::current();

        if ($organization === null) {
            return [];
        }

        $people = $user->can(OrganizationPermission::AssignTasks->value)
            ? $organization->users()->get(['users.id', 'first_name', 'last_name'])
            : $user->directReportsIn($organization)->push($user);

        $options = [];

        foreach ($people->unique(fn (User $person): int => $person->getKey()) as $person) {
            // A person loaded without their key has no code, and so no way to
            // be named in the form that comes back. Skipped rather than offered
            // as an option that could not be submitted.
            if (! is_string($person->hash_id)) {
                continue;
            }

            $options[] = [
                'hash_id' => $person->hash_id,
                'name' => $person->full_name,
            ];
        }

        return $options;
    }
}
