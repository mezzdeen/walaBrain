<?php

namespace App\Modules\Boards\Policies;

use App\Modules\Boards\Models\Node;
use App\Modules\Core\Models\Admin;
use App\Modules\Core\Models\User;

/**
 * Authorizes what someone may do to the work on a board.
 *
 * Deliberately separate from the board's own definition: working a board and
 * designing one are different things, and somebody who fills in requests all
 * day should not need the authority to redefine the process while they do it.
 *
 * One exception runs through all of it. Being assigned a node makes it
 * reachable whatever the space says, because work routed to somebody is work
 * they are expected to do, and refusing them the item they were told to act on
 * would be the platform arguing with itself.
 */
class NodePolicy
{
    /**
     * Determine whether the identity can list nodes.
     */
    public function viewAny(User|Admin $identity): bool
    {
        return $identity instanceof User;
    }

    /**
     * Determine whether the identity can open the node.
     */
    public function view(User|Admin $identity, Node $node): bool
    {
        return $identity instanceof User
            && ($this->isAssigned($identity, $node)
                || $this->isCreator($identity, $node)
                || $identity->can('view', $node->board->space));
    }

    /**
     * Determine whether the identity can add work to the board.
     */
    public function create(User|Admin $identity, Node $node): bool
    {
        return $identity instanceof User && $identity->can('edit', $node->board->space);
    }

    /**
     * Determine whether the identity can change the node.
     *
     * The assignee can, wherever it lives: completing work means editing it.
     */
    public function update(User|Admin $identity, Node $node): bool
    {
        return $identity instanceof User
            && ($this->isAssigned($identity, $node) || $identity->can('edit', $node->board->space));
    }

    /**
     * Determine whether the identity can revise and resubmit the node.
     *
     * Only its submitter, and only while it has been handed back to them —
     * which is what the changes_requested status says. The status is written
     * by whatever automation sent it back; this policy only reads it.
     */
    public function resubmit(User|Admin $identity, Node $node): bool
    {
        return $identity instanceof User
            && $this->isCreator($identity, $node)
            && $node->status === 'changes_requested';
    }

    /**
     * Determine whether the identity can remove the node.
     *
     * Not extended to the assignee: being asked to do something is not
     * authority to make it disappear.
     */
    public function delete(User|Admin $identity, Node $node): bool
    {
        return $identity instanceof User && $identity->can('edit', $node->board->space);
    }

    /**
     * Whether the node is waiting on this person.
     */
    private function isAssigned(User $user, Node $node): bool
    {
        return $node->assignee_id === $user->getKey();
    }

    /**
     * Whether this person submitted the node.
     *
     * A submitter follows their own request wherever it went — a finance
     * request lives on a finance board its requester cannot browse, and being
     * unable to see what happened to your own submission would defeat the
     * point of tracking it.
     */
    private function isCreator(User $user, Node $node): bool
    {
        return $node->creator_id === $user->getKey();
    }
}
