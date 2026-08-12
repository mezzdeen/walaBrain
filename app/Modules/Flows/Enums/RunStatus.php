<?php

namespace App\Modules\Flows\Enums;

/**
 * Where a run stands. Waiting is the ordinary state — a run spends its life
 * paused at whichever step needs a person — and the other two are the ends it
 * can reach. A request-changes decision is not a state of the run: the run
 * stays Waiting at the same step while the submitter revises.
 */
enum RunStatus: string
{
    case Waiting = 'waiting';
    case Completed = 'completed';
    case Rejected = 'rejected';
}
