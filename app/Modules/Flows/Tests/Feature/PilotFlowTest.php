<?php

use App\Modules\Boards\Enums\FieldType;
use App\Modules\Boards\Models\Board;
use App\Modules\Boards\Models\Field;
use App\Modules\Boards\Models\Node;
use App\Modules\Core\Models\Notification as CoreNotification;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\OrganizationContext;
use App\Modules\Core\Support\OrganizationMembers;
use App\Modules\Flows\Enums\ApprovalStatus;
use App\Modules\Flows\Enums\RunStatus;
use App\Modules\Flows\Enums\StepType;
use App\Modules\Flows\Models\Approval;
use App\Modules\Flows\Models\Flow;
use App\Modules\Flows\Models\Run;
use App\Modules\Forms\Models\Form;

/*
|--------------------------------------------------------------------------
| The Pilot Flow
|--------------------------------------------------------------------------
|
| The finance payment request end to end: a fixed two-approver chain — the
| requester's manager, then the finance manager — and an execution task due
| a calendar offset after submission. The process the whole phase exists to
| carry.
|
*/

/**
 * The pilot, assembled: form, flow, chain, and the people in it.
 *
 * @return array{organization: Organization, form: Form, amount: Field, requester: User, manager: User, finance: User}
 */
function pilot(): array
{
    $organization = Organization::factory()->create();

    $requester = memberOf($organization);
    $manager = memberOf($organization);
    $finance = memberOf($organization);

    OrganizationMembers::setManager($organization, $requester, $manager);

    return OrganizationContext::for($organization, function () use ($organization, $requester, $manager, $finance): array {
        $board = Board::factory()->create();
        $amount = Field::factory()->on($board)->ofType(FieldType::Money)->create(['name' => 'Amount', 'is_required' => true]);

        $form = Form::factory()->on($board)->create(['prefix' => 'FIN']);

        $flow = Flow::factory()->for_($form)->create(['name' => 'Payment approval']);

        $flow->steps()->createMany([
            ['position' => 1, 'type' => StepType::Approval, 'config' => ['assignee_type' => 'manager']],
            ['position' => 2, 'type' => StepType::Approval, 'config' => ['assignee_type' => 'user', 'assignee_id' => $finance->getKey()]],
            ['position' => 3, 'type' => StepType::Task, 'config' => [
                'assignee_type' => 'user',
                'assignee_id' => $finance->getKey(),
                'title' => 'Execute payment',
                'due_offset_days' => 3,
            ]],
        ]);

        return compact('organization', 'form', 'amount', 'requester', 'manager', 'finance');
    });
}

/**
 * The pilot, with its organization made the active one.
 *
 * @return array{organization: Organization, form: Form, amount: Field, requester: User, manager: User, finance: User}
 */
function activePilot(): array
{
    $pilot = pilot();

    OrganizationContext::switch($pilot['organization']);

    return $pilot;
}

/**
 * The submitted request's node.
 *
 * @param  array{organization: Organization, form: Form, amount: Field, requester: User, manager: User, finance: User}  $pilot
 */
function pilotNode(array $pilot): Node
{
    return OrganizationContext::for(
        $pilot['organization'],
        fn (): Node => Node::query()->whereNotNull('reference')->firstOrFail(),
    );
}

test('submission starts a run waiting on the requester\'s manager', function () {
    $pilot = activePilot();

    $this->actingAs($pilot['requester'])
        ->post(route('forms.store', $pilot['form']), [
            'values' => [$pilot['amount']->hash_id => '2500'],
        ])
        ->assertSessionHasNoErrors();

    $node = pilotNode($pilot);

    $run = OrganizationContext::for($pilot['organization'], fn () => Run::query()->firstOrFail());
    $approval = OrganizationContext::for($pilot['organization'], fn () => Approval::query()->firstOrFail());

    expect($run->status)->toBe(RunStatus::Waiting)
        ->and($approval->approver_id)->toBe($pilot['manager']->getKey())
        ->and($approval->isPending())->toBeTrue()
        // The approver was told, in app and somewhere a deep link can live.
        ->and($pilot['manager']->notifications()->count())->toBe(1)
        ->and($node->activities()->where('type', 'approval.requested')->count())->toBe(1);
});

test('the full chain: manager approves, finance approves, the task exists', function () {
    $pilot = activePilot();

    $this->actingAs($pilot['requester'])
        ->post(route('forms.store', $pilot['form']), [
            'values' => [$pilot['amount']->hash_id => '2500'],
        ])
        ->assertSessionHasNoErrors();

    $node = pilotNode($pilot);

    $first = OrganizationContext::for($pilot['organization'], fn () => Approval::query()->firstOrFail());

    $this->actingAs($pilot['manager'])
        ->post(route('approvals.store', $first), ['decision' => 'approved'])
        ->assertRedirect(route('my-work.index'));

    // The chain moved on: a fresh pending approval for the finance manager.
    $second = OrganizationContext::for(
        $pilot['organization'],
        fn () => Approval::query()->pendingFor($pilot['finance'])->firstOrFail(),
    );

    $this->actingAs($pilot['finance'])
        ->post(route('approvals.store', $second), ['decision' => 'approved'])
        ->assertRedirect();

    OrganizationContext::for($pilot['organization'], function () use ($pilot, $node): void {
        $run = Run::query()->firstOrFail();
        $task = Node::query()->where('title', 'Execute payment')->firstOrFail();

        expect($run->status)->toBe(RunStatus::Completed)
            ->and($node->fresh()->status)->toBe('approved')
            // Due three calendar days after submission, assigned to finance.
            ->and($task->assignee_id)->toBe($pilot['finance']->getKey())
            ->and($task->due_date->toDateString())->toBe(now()->addDays(3)->toDateString())
            ->and($task->description)->toContain((string) $node->reference);
    });

    // The requester heard about each decision; finance heard about the task.
    expect($pilot['requester']->notifications()->count())->toBe(2)
        ->and($pilot['finance']->notifications()->count())->toBe(2);
});

test('a rejection ends the run and the request says so', function () {
    $pilot = activePilot();

    $this->actingAs($pilot['requester'])
        ->post(route('forms.store', $pilot['form']), [
            'values' => [$pilot['amount']->hash_id => '2500'],
        ])
        ->assertSessionHasNoErrors();

    $node = pilotNode($pilot);

    $approval = OrganizationContext::for($pilot['organization'], fn () => Approval::query()->firstOrFail());

    $this->actingAs($pilot['manager'])
        ->post(route('approvals.store', $approval), [
            'decision' => 'rejected',
            'comment' => 'No budget line for this.',
        ])
        ->assertSessionHasNoErrors();

    OrganizationContext::for($pilot['organization'], function () use ($node): void {
        expect(Run::query()->firstOrFail()->status)->toBe(RunStatus::Rejected)
            ->and($node->fresh()->status)->toBe('rejected')
            // No second approval was ever asked for, and no task exists.
            ->and(Approval::count())->toBe(1)
            ->and(Node::query()->where('title', 'Execute payment')->exists())->toBeFalse();
    });

    expect($node->activities()->where('type', 'approval.rejected')->count())->toBe(1);
});

test('rejecting without a comment is refused', function () {
    $pilot = activePilot();

    $this->actingAs($pilot['requester'])
        ->post(route('forms.store', $pilot['form']), [
            'values' => [$pilot['amount']->hash_id => '2500'],
        ])
        ->assertSessionHasNoErrors();

    $approval = OrganizationContext::for($pilot['organization'], fn () => Approval::query()->firstOrFail());

    $this->actingAs($pilot['manager'])
        ->post(route('approvals.store', $approval), ['decision' => 'rejected'])
        ->assertSessionHasErrors('comment');

    expect($approval->fresh()->isPending())->toBeTrue();
});

test('request changes hands the node back, and resubmitting re-pends the same step', function () {
    $pilot = activePilot();

    $this->actingAs($pilot['requester'])
        ->post(route('forms.store', $pilot['form']), [
            'values' => [$pilot['amount']->hash_id => '2500'],
        ])
        ->assertSessionHasNoErrors();

    $node = pilotNode($pilot);

    $first = OrganizationContext::for($pilot['organization'], fn () => Approval::query()->firstOrFail());

    $this->actingAs($pilot['manager'])
        ->post(route('approvals.store', $first), [
            'decision' => 'changes_requested',
            'comment' => 'Name the beneficiary properly.',
        ])
        ->assertSessionHasNoErrors();

    expect($node->fresh()->status)->toBe('changes_requested')
        // The run never left the step, and was never rejected.
        ->and(OrganizationContext::for($pilot['organization'], fn () => Run::query()->firstOrFail())->status)
        ->toBe(RunStatus::Waiting);

    // The submitter revises and resubmits — same node, same reference.
    $reference = $node->reference;

    $this->actingAs($pilot['requester'])
        ->post(route('nodes.resubmit', $node), [
            'values' => [$pilot['amount']->hash_id => '2600'],
        ])
        ->assertSessionHasNoErrors();

    $node = $node->fresh();

    expect($node->reference)->toBe($reference)
        ->and($node->status)->toBe('in_review')
        // toEqual, not toBe: json round-trips a whole 2600.0 back as int 2600,
        // and the storage contract asks for "a number", which both are.
        ->and($node->valueFor($pilot['amount']->fresh()))->toEqual(2600);

    // A fresh pending approval at the same step, for the same manager; the
    // changes_requested round stays on record untouched.
    OrganizationContext::for($pilot['organization'], function () use ($pilot): void {
        expect(Approval::query()->pendingFor($pilot['manager'])->count())->toBe(1)
            ->and(Approval::query()->where('status', ApprovalStatus::ChangesRequested->value)->count())->toBe(1);
    });
});

test('somebody else cannot resubmit, and the submitter cannot before changes are asked', function () {
    $pilot = activePilot();

    $this->actingAs($pilot['requester'])
        ->post(route('forms.store', $pilot['form']), [
            'values' => [$pilot['amount']->hash_id => '2500'],
        ])
        ->assertSessionHasNoErrors();

    $node = pilotNode($pilot);

    // Not sent back yet: even the submitter has nothing to resubmit.
    $this->actingAs($pilot['requester'])
        ->post(route('nodes.resubmit', $node), ['values' => []])
        ->assertForbidden();

    $approval = OrganizationContext::for($pilot['organization'], fn () => Approval::query()->firstOrFail());

    $this->actingAs($pilot['manager'])
        ->post(route('approvals.store', $approval), [
            'decision' => 'changes_requested',
            'comment' => 'Revise.',
        ]);

    // Sent back — but to its submitter, not to whoever else is around.
    $this->actingAs($pilot['manager'])
        ->post(route('nodes.resubmit', $node->fresh()), ['values' => []])
        ->assertForbidden();
});

test('an approval is decidable by its approver alone, and only once', function () {
    $pilot = activePilot();

    $this->actingAs($pilot['requester'])
        ->post(route('forms.store', $pilot['form']), [
            'values' => [$pilot['amount']->hash_id => '2500'],
        ])
        ->assertSessionHasNoErrors();

    $approval = OrganizationContext::for($pilot['organization'], fn () => Approval::query()->firstOrFail());

    // The requester cannot decide their own request's approval.
    $this->actingAs($pilot['requester'])
        ->post(route('approvals.store', $approval), ['decision' => 'approved'])
        ->assertForbidden();

    $this->actingAs($pilot['manager'])
        ->post(route('approvals.store', $approval), ['decision' => 'approved']);

    // Recorded decisions are never edited: a second attempt is refused.
    $this->actingAs($pilot['manager'])
        ->post(route('approvals.store', $approval->fresh()), ['decision' => 'rejected', 'comment' => 'changed my mind'])
        ->assertForbidden();
});

test('a submitter with no manager is blocked with a clear error, and nothing is created', function () {
    $pilot = pilot();

    // The manager submits: she reports to nobody, and the first step of the
    // chain is "the requester's manager".
    OrganizationContext::switch($pilot['organization']);

    $this->actingAs($pilot['manager'])
        ->post(route('forms.store', $pilot['form']), [
            'values' => [$pilot['amount']->hash_id => '100'],
        ])
        ->assertSessionHasErrors('form');

    // The whole submission rolled back: no orphaned node sits invisible with
    // a run that never started.
    OrganizationContext::for($pilot['organization'], function (): void {
        expect(Node::count())->toBe(0)
            ->and(Run::count())->toBe(0);
    });
});

test('my work lists the pending approval, and deciding clears it', function () {
    $pilot = activePilot();

    $this->actingAs($pilot['requester'])
        ->post(route('forms.store', $pilot['form']), [
            'values' => [$pilot['amount']->hash_id => '2500'],
        ])
        ->assertSessionHasNoErrors();

    OrganizationContext::switch($pilot['organization']);

    $this->actingAs($pilot['manager'])
        ->get(route('my-work.index'))
        ->assertInertia(fn ($page) => $page
            ->component('my-work')
            ->has('approvals', 1)
            ->where('approvals.0.title', $pilot['form']->name));

    $approval = OrganizationContext::for($pilot['organization'], fn () => Approval::query()->firstOrFail());
    $this->post(route('approvals.store', $approval), ['decision' => 'approved']);

    $this->get(route('my-work.index'))
        ->assertInertia(fn ($page) => $page->has('approvals', 0));
});

test('an in-app notification carries a key, its parameters, and a deep link', function () {
    $pilot = activePilot();

    $this->actingAs($pilot['requester'])
        ->post(route('forms.store', $pilot['form']), [
            'values' => [$pilot['amount']->hash_id => '2500'],
        ])
        ->assertSessionHasNoErrors();

    $notification = CoreNotification::query()
        ->where('notifiable_id', $pilot['manager']->getKey())
        ->firstOrFail();

    expect($notification->data['key'])->toBe('flows.notifications.approval_requested')
        ->and($notification->data['params']['reference'])->toContain('FIN-')
        ->and($notification->data['url'])->toContain('/approvals/')
        ->and($notification->organization_id)->toBe($pilot['organization']->getKey());
});

test('a run in one business line is untouchable from another', function () {
    $pilot = activePilot();

    $this->actingAs($pilot['requester'])
        ->post(route('forms.store', $pilot['form']), [
            'values' => [$pilot['amount']->hash_id => '2500'],
        ])
        ->assertSessionHasNoErrors();

    $approval = OrganizationContext::for($pilot['organization'], fn () => Approval::query()->firstOrFail());

    $elsewhere = Organization::factory()->create();
    $pilot['manager']->organizations()->attach($elsewhere);

    // The approver themselves, but working in the wrong business line: the
    // approval does not exist from there.
    OrganizationContext::switch($elsewhere);

    $this->actingAs($pilot['manager'])
        ->get(route('approvals.show', $approval))
        ->assertNotFound();
});
