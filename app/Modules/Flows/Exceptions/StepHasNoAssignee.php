<?php

namespace App\Modules\Flows\Exceptions;

use App\Modules\Core\Contracts\HaltsSubmission;
use App\Modules\Flows\Models\FlowStep;
use RuntimeException;

/**
 * Thrown when a step reaches for a person and finds nobody — a manager step
 * for a submitter who reports to no one, or a named user who no longer exists.
 *
 * Loud on purpose. A step that silently skips its approver is an approval
 * that never happened. Thrown inside the submission's transaction, it rolls
 * the submission back whole, and the contract it implements is what lets
 * intake turn it into a clear validation error rather than a server error.
 */
final class StepHasNoAssignee extends RuntimeException implements HaltsSubmission
{
    /**
     * What the submitter can actually do about it.
     */
    public function submitterMessage(): string
    {
        return __('flows::flows.no_manager');
    }

    public static function at(FlowStep $step): self
    {
        return new self(sprintf(
            'Step [%d] of flow [%d] resolves to nobody. A manager-assigned step needs the submitter to have a manager.',
            $step->position,
            $step->flow_id,
        ));
    }
}
