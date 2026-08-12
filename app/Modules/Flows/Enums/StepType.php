<?php

namespace App\Modules\Flows\Enums;

/**
 * The step types this phase ships. Two of the seven the model defines:
 * approvals and tasks are what the pilot process is made of. Condition, Wait,
 * Notify and Book Slot follow with the phases that need them; Trigger is not a
 * row at all here, because a flow's one trigger is the form it is attached to.
 */
enum StepType: string
{
    /** Ask somebody to approve, reject, or request changes. */
    case Approval = 'approval';

    /** Create a work assignment with its own due date. */
    case Task = 'task';
}
