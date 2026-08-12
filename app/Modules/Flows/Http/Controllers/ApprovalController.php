<?php

namespace App\Modules\Flows\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Boards\Models\Field;
use App\Modules\Core\Models\User;
use App\Modules\Flows\Enums\ApprovalStatus;
use App\Modules\Flows\Models\Approval;
use App\Modules\Flows\Support\Runner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The decision screen: the request's details, the earlier rounds of the same
 * conversation, and the three decisions.
 *
 * Reached from My Work and from the email deep link alike — both land here,
 * and the recorded outcome is the same whichever door was used.
 */
class ApprovalController extends Controller
{
    public function __construct(private readonly Runner $runner) {}

    /**
     * The decision screen for one approval.
     */
    public function show(Approval $approval): Response
    {
        Gate::authorize('view', $approval);

        $node = $approval->node;
        $fields = $node->board->fields;

        return Inertia::render('approvals/show', [
            'approval' => [
                'hash_id' => $approval->hash_id,
                'status' => $approval->status->value,
                'is_pending' => $approval->isPending(),
            ],
            'node' => [
                'hash_id' => $node->hash_id,
                'title' => $node->title,
                'reference' => $node->reference,
                'status' => $node->status,
                'submitter' => $node->creator?->full_name,
                'submitted_at' => $node->created_at?->toDateString(),
            ],
            'values' => $fields->map(fn (Field $field): array => [
                'name' => $field->name,
                'type' => $field->type->value,
                'value' => $node->valueFor($field),
            ])->all(),

            // The earlier rounds of this same request: every decision already
            // recorded on the run, so a second approver sees what the first
            // said, and a resubmission shows what it was asked to change.
            'history' => $approval->run->approvals()
                ->where('id', '!=', $approval->getKey())
                ->whereNot('status', ApprovalStatus::Pending->value)
                ->orderBy('id')
                ->get()
                ->map(fn (Approval $earlier): array => [
                    'approver' => $earlier->approver->full_name,
                    'status' => $earlier->status->value,
                    'comment' => $earlier->comment,
                    'decided_at' => $earlier->decided_at?->toDateTimeString(),
                ])->all(),
        ]);
    }

    /**
     * Record the decision.
     */
    public function store(Request $request, Approval $approval): RedirectResponse
    {
        Gate::authorize('decide', $approval);

        /** @var array{decision: string, comment: string|null} $validated */
        $validated = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected', 'changes_requested'])],

            // A refusal without a reason is a conversation that moves to
            // email; the two negative decisions require the comment.
            'comment' => ['required_unless:decision,approved', 'nullable', 'string', 'max:2000'],
        ]);

        /** @var User $approver */
        $approver = $request->user();

        $this->runner->decide(
            $approval,
            ApprovalStatus::from($validated['decision']),
            $validated['comment'] ?? null,
            $approver,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('flows::flows.decided'),
        ]);

        return to_route('my-work.index');
    }
}
