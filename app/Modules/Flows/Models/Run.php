<?php

namespace App\Modules\Flows\Models;

use App\Modules\Boards\Models\Node;
use App\Modules\Core\Concerns\BelongsToOrganization;
use App\Modules\Flows\Enums\RunStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One execution of a flow for one node, from trigger to whichever end it
 * reaches. It pauses at whatever needs a person and picks up exactly where it
 * left off — from the requester's side, a request that quietly keeps moving.
 *
 * @property int $id
 * @property int $organization_id
 * @property int $flow_id
 * @property int $node_id
 * @property RunStatus $status
 * @property int|null $current_step_id
 * @property int $flow_version
 * @property int $form_version
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['flow_id', 'node_id', 'status', 'current_step_id', 'flow_version', 'form_version'])]
class Run extends Model
{
    use BelongsToOrganization;

    /**
     * The flow being executed.
     *
     * @return BelongsTo<Flow, $this>
     */
    public function flow(): BelongsTo
    {
        return $this->belongsTo(Flow::class);
    }

    /**
     * The node the run is moving.
     *
     * @return BelongsTo<Node, $this>
     */
    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }

    /**
     * The step the run is paused at, while it is paused at one.
     *
     * @return BelongsTo<FlowStep, $this>
     */
    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(FlowStep::class, 'current_step_id');
    }

    /**
     * Every decision asked for during the run, across every round.
     *
     * @return HasMany<Approval, $this>
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RunStatus::class,
            'flow_version' => 'integer',
            'form_version' => 'integer',
        ];
    }
}
