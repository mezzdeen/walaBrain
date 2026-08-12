<?php

namespace App\Modules\Flows\Support;

use App\Modules\Boards\Models\Node;
use App\Modules\Core\Models\User;
use App\Modules\Flows\Enums\ApprovalStatus;
use App\Modules\Flows\Enums\RunStatus;
use App\Modules\Flows\Enums\StepType;
use App\Modules\Flows\Exceptions\StepHasNoAssignee;
use App\Modules\Flows\Models\Approval;
use App\Modules\Flows\Models\Flow;
use App\Modules\Flows\Models\FlowStep;
use App\Modules\Flows\Models\Run;
use App\Modules\Flows\Notifications\ApprovalRequested;
use App\Modules\Flows\Notifications\DecisionRecorded;
use App\Modules\Flows\Notifications\TaskAssigned;
use Illuminate\Support\Facades\DB;

/**
 * Moves runs through their steps.
 *
 * The engine is deliberately small: execute steps in order, pause at anything
 * that needs a person, resume when they act. Every pause and every decision
 * lands on the node's timeline and in somebody's notifications, because a
 * process nobody can see moving is a process everyone chases by email again.
 */
class Runner
{
    /**
     * Start a run for a freshly submitted node.
     */
    public function start(Flow $flow, Node $node): Run
    {
        return DB::transaction(function () use ($flow, $node): Run {
            $run = Run::create([
                'flow_id' => $flow->getKey(),
                'node_id' => $node->getKey(),
                'status' => RunStatus::Waiting,
                'flow_version' => $flow->version,
                'form_version' => $flow->form->version,
            ]);

            // Which definitions the run operates under, so the timeline stays
            // meaningful after either is republished.
            $node->recordActivity('run.started', [
                'flow' => $flow->name,
                'flow_version' => $flow->version,
                'form_version' => $flow->form->version,
            ], null);

            $this->advance($run);

            return $run;
        });
    }

    /**
     * Execute steps from wherever the run stands until something needs a
     * person or the sequence ends.
     */
    public function advance(Run $run): void
    {
        $step = $this->stepAfter($run->flow, $run->currentStep);

        while ($step !== null) {
            if ($step->type === StepType::Approval) {
                $this->openApproval($run, $step);

                return;
            }

            $this->createTask($run, $step);

            $run->forceFill(['current_step_id' => $step->getKey()])->save();
            $step = $this->stepAfter($run->flow, $step);
        }

        $run->forceFill(['status' => RunStatus::Completed, 'current_step_id' => null])->save();

        $node = $run->node;
        $node->forceFill(['status' => 'approved'])->save();
        $node->recordActivity('run.completed', [], null);
    }

    /**
     * Record a decision and move the run accordingly.
     */
    public function decide(Approval $approval, ApprovalStatus $decision, ?string $comment, User $approver): void
    {
        DB::transaction(function () use ($approval, $decision, $comment, $approver): void {
            $approval->forceFill([
                'status' => $decision,
                'comment' => $comment,
                'decided_at' => now(),
            ])->save();

            $run = $approval->run;
            $node = $approval->node;

            // Permanent, with the decision-maker, timestamp and comment: the
            // record an audit reads back, whatever the notification said.
            $node->recordActivity('approval.'.$decision->value, [
                'comment' => $comment,
                'step' => $approval->flow_step_id,
            ], $approver);

            $this->notifySubmitter($node, $decision, $comment, $approver);

            match ($decision) {
                ApprovalStatus::Approved => $this->continueFrom($run, $approval),
                ApprovalStatus::Rejected => $this->reject($run),
                // The run stays waiting at this step; the node goes back to
                // its submitter to revise and resubmit.
                ApprovalStatus::ChangesRequested => $node->forceFill(['status' => 'changes_requested'])->save(),
                ApprovalStatus::Pending => null,
            };
        });
    }

    /**
     * Bring a revised node back to the step it is waiting at.
     *
     * A fresh pending approval rather than the old row reopened, and the
     * approver resolved again — a manager may have changed while the submitter
     * revised, and the decision belongs to whoever holds the role now.
     */
    public function resubmit(Run $run, User $submitter): void
    {
        DB::transaction(function () use ($run, $submitter): void {
            $node = $run->node;

            $node->forceFill(['status' => 'in_review'])->save();
            $node->recordActivity('form.resubmitted', [], $submitter);

            $step = $run->currentStep;

            if ($step !== null) {
                $this->openApproval($run, $step);
            }
        });
    }

    /**
     * Pause the run at an approval step, and tell whoever it now waits on.
     */
    private function openApproval(Run $run, FlowStep $step): void
    {
        $approver = $step->resolveAssignee($run->node);

        if ($approver === null) {
            throw StepHasNoAssignee::at($step);
        }

        $run->forceFill(['status' => RunStatus::Waiting, 'current_step_id' => $step->getKey()])->save();

        $approval = Approval::create([
            'run_id' => $run->getKey(),
            'flow_step_id' => $step->getKey(),
            'node_id' => $run->node_id,
            'approver_id' => $approver->getKey(),
            'status' => ApprovalStatus::Pending,
        ]);

        $run->node->recordActivity('approval.requested', [
            'approver' => $approver->full_name,
        ], null);

        $approver->notify(new ApprovalRequested($approval));
    }

    /**
     * Generate the work a task step describes: a node on the flow's board,
     * due a calendar-day offset from submission.
     */
    private function createTask(Run $run, FlowStep $step): void
    {
        $source = $run->node;
        $assignee = $step->resolveAssignee($source);

        if ($assignee === null) {
            throw StepHasNoAssignee::at($step);
        }

        $offset = (int) ($step->config['due_offset_days'] ?? 0);

        $task = Node::create([
            'board_id' => $source->board_id,
            'title' => (string) ($step->config['title'] ?? $source->title),
            'description' => $source->reference !== null
                ? __('flows::flows.task_for', ['reference' => $source->reference])
                : null,
            'assignee_id' => $assignee->getKey(),
            'due_date' => $source->created_at?->clone()->addDays($offset)->toDateString(),
        ]);

        $task->recordActivity('task.created', ['from' => $source->reference], null);
        $source->recordActivity('task.generated', [
            'title' => $task->title,
            'assignee' => $assignee->full_name,
        ], null);

        $assignee->notify(new TaskAssigned($task));
    }

    /**
     * Carry on past an approved step.
     */
    private function continueFrom(Run $run, Approval $approval): void
    {
        $run->forceFill(['current_step_id' => $approval->flow_step_id])->save();

        $this->advance($run->fresh(['flow', 'currentStep', 'node']));
    }

    /**
     * End the run at a rejection.
     */
    private function reject(Run $run): void
    {
        $run->forceFill(['status' => RunStatus::Rejected, 'current_step_id' => null])->save();

        $node = $run->node;
        $node->forceFill(['status' => 'rejected'])->save();
        $node->recordActivity('run.rejected', [], null);
    }

    /**
     * Tell the submitter what was decided about their request.
     */
    private function notifySubmitter(Node $node, ApprovalStatus $decision, ?string $comment, User $approver): void
    {
        $submitter = $node->creator;

        if ($submitter !== null && ! $submitter->is($approver)) {
            $submitter->notify(new DecisionRecorded($node, $decision, $comment));
        }
    }

    /**
     * The next step in sequence, or the first when none has run yet.
     */
    private function stepAfter(Flow $flow, ?FlowStep $current): ?FlowStep
    {
        $query = $flow->steps()->orderBy('position');

        if ($current !== null) {
            $query->where('position', '>', $current->position);
        }

        return $query->first();
    }
}
