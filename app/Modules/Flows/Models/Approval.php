<?php

namespace App\Modules\Flows\Models;

use App\Modules\Boards\Models\Node;
use App\Modules\Core\Concerns\BelongsToOrganization;
use App\Modules\Core\Concerns\HasHashId;
use App\Modules\Core\Models\User;
use App\Modules\Flows\Enums\ApprovalStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One person's decision on one request, asked for once and answered once.
 *
 * A request-changes loop asks again with a fresh row rather than reopening
 * this one, so every round of the back-and-forth stays on record — a decision,
 * once made, is never edited or withdrawn.
 *
 * @property int $id
 * @property int $organization_id
 * @property int $run_id
 * @property int $flow_step_id
 * @property int $node_id
 * @property int $approver_id
 * @property ApprovalStatus $status
 * @property string|null $comment
 * @property Carbon|null $decided_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['run_id', 'flow_step_id', 'node_id', 'approver_id', 'status', 'comment', 'decided_at'])]
class Approval extends Model
{
    use BelongsToOrganization, HasHashId;

    /**
     * The run waiting on this decision.
     *
     * @return BelongsTo<Run, $this>
     */
    public function run(): BelongsTo
    {
        return $this->belongsTo(Run::class);
    }

    /**
     * The step that asked for it.
     *
     * @return BelongsTo<FlowStep, $this>
     */
    public function step(): BelongsTo
    {
        return $this->belongsTo(FlowStep::class, 'flow_step_id');
    }

    /**
     * The request being decided.
     *
     * @return BelongsTo<Node, $this>
     */
    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }

    /**
     * Who decides.
     *
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * Whether the decision is still to be made.
     */
    public function isPending(): bool
    {
        return $this->status === ApprovalStatus::Pending;
    }

    /**
     * Constrain to the decisions waiting on one person.
     *
     * @param  Builder<Approval>  $query
     * @return Builder<Approval>
     */
    public function scopePendingFor(Builder $query, User $user): Builder
    {
        return $query->where('approver_id', $user->getKey())
            ->where('status', ApprovalStatus::Pending->value)
            ->orderBy('id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ApprovalStatus::class,
            'decided_at' => 'datetime',
        ];
    }
}
