<?php

namespace App\Modules\Flows\Models;

use App\Modules\Boards\Models\Node;
use App\Modules\Core\Concerns\BelongsToOrganization;
use App\Modules\Core\Models\User;
use App\Modules\Flows\Enums\StepType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One step in a flow's sequence.
 *
 * The config carries what varies by type. An approval: `assignee_type`
 * ('user'|'manager') and `assignee_id` where a user is named. A task: the
 * same two, plus `title` and `due_offset_days`, calendar days counted from
 * submission — the anchor this phase ships; the rest arrive with business-day
 * maths in the next.
 *
 * @property int $id
 * @property int $organization_id
 * @property int $flow_id
 * @property int $position
 * @property StepType $type
 * @property array<string, mixed> $config
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['organization_id', 'flow_id', 'position', 'type', 'config'])]
class FlowStep extends Model
{
    use BelongsToOrganization;

    /**
     * The flow the step belongs to.
     *
     * @return BelongsTo<Flow, $this>
     */
    public function flow(): BelongsTo
    {
        return $this->belongsTo(Flow::class);
    }

    /**
     * Who this step's approval or task lands on, for the given node.
     *
     * Two of the four assignment rules, resolved at the moment the step is
     * reached: a person named at design time, or whoever the submitter reports
     * to when the run arrives here. Null when a manager step finds no manager —
     * the caller decides what a stalled step means, because silence must not.
     */
    public function resolveAssignee(Node $node): ?User
    {
        $type = $this->config['assignee_type'] ?? null;

        if ($type === 'user') {
            $id = $this->config['assignee_id'] ?? null;

            return is_int($id) ? User::query()->find($id) : null;
        }

        if ($type === 'manager') {
            $creator = $node->creator;
            $organization = $node->organization;

            return ($creator !== null && $organization !== null)
                ? $creator->managerIn($organization)
                : null;
        }

        return null;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => StepType::class,
            'config' => 'array',
            'position' => 'integer',
        ];
    }
}
