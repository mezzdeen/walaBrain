<?php

namespace App\Modules\Boards\Policies;

use App\Modules\Boards\Models\Board;
use App\Modules\Core\Enums\OrganizationPermission;
use App\Modules\Core\Models\Admin;
use App\Modules\Core\Models\User;

/**
 * Authorizes what someone may do to a board.
 *
 * Two questions again, and the same two as spaces. **Where** someone may act is
 * the space the board lives in, answered by membership. **What** they may do
 * there is a capability: working a board needs only edit access to its space,
 * while changing the board itself — its fields, its groups, the forms and flows
 * that will hang off it — needs Process Designer as well.
 *
 * Nothing is asked about the board's own contents here; that is {@see NodePolicy}.
 */
class BoardPolicy
{
    /**
     * Determine whether the identity can list the boards in a space.
     *
     * Answered per board by {@see self::view()}: somebody sees the boards in the
     * spaces they belong to, which is what the listing is filtered by.
     */
    public function viewAny(User|Admin $identity): bool
    {
        return $identity instanceof User;
    }

    /**
     * Determine whether the identity can open the board.
     */
    public function view(User|Admin $identity, Board $board): bool
    {
        return $identity instanceof User && $identity->can('view', $board->space);
    }

    /**
     * Determine whether the identity can design boards in the space.
     *
     * Both halves are needed: the capability says they design processes at all,
     * the space membership says where. Holding one without the other is
     * somebody who may design but has nowhere to, or somebody in the right place
     * without the authority.
     */
    public function create(User|Admin $identity, Board $board): bool
    {
        return $identity instanceof User
            && $identity->can(OrganizationPermission::DesignProcesses->value)
            && $identity->can('edit', $board->space);
    }

    /**
     * Determine whether the identity can change the board's own definition —
     * its name, its fields, its groups.
     */
    public function update(User|Admin $identity, Board $board): bool
    {
        return $this->create($identity, $board);
    }

    /**
     * Determine whether the identity can remove the board.
     *
     * The default board is refused to everyone: work that belongs to no process
     * would have nowhere left to live.
     */
    public function delete(User|Admin $identity, Board $board): bool
    {
        return ! $board->isProtected() && $this->create($identity, $board);
    }
}
