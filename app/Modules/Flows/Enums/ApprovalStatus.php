<?php

namespace App\Modules\Flows\Enums;

/**
 * One approval's lifecycle. A recorded decision is never edited or withdrawn;
 * a changed mind needs the run to come back around, not history to move.
 */
enum ApprovalStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case ChangesRequested = 'changes_requested';
}
