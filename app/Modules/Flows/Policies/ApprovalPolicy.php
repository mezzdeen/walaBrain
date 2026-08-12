<?php

namespace App\Modules\Flows\Policies;

use App\Modules\Core\Models\Admin;
use App\Modules\Core\Models\User;
use App\Modules\Flows\Models\Approval;

/**
 * Authorizes who may see and decide an approval.
 *
 * The approver, and nobody else. An approval routed to somebody makes its
 * node's details visible to them through this page whatever the space says —
 * the same principle as an assigned task: work sent to a person must be
 * actionable by that person.
 */
class ApprovalPolicy
{
    /**
     * Determine whether the identity can open the decision screen.
     */
    public function view(User|Admin $identity, Approval $approval): bool
    {
        return $identity instanceof User
            && $approval->approver_id === $identity->getKey();
    }

    /**
     * Determine whether the identity can record the decision.
     *
     * Only while it is still pending: a recorded decision is never edited or
     * withdrawn, so there is nothing here to authorize twice.
     */
    public function decide(User|Admin $identity, Approval $approval): bool
    {
        return $this->view($identity, $approval) && $approval->isPending();
    }
}
