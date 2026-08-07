<?php

namespace App\Modules\Core\Policies;

use App\Modules\Core\Enums\OrganizationPermission;
use App\Modules\Core\Models\Admin;
use App\Modules\Core\Models\Space;
use App\Modules\Core\Models\User;

/**
 * Authorizes what someone may do to and inside a space.
 *
 * Two questions, deliberately answered by two different things. Whether the
 * space itself may be created, renamed, removed or staffed is a **capability**,
 * held through a role. Whether its contents may be opened or changed is
 * **membership**, granted per space. Somebody who administers spaces therefore
 * reaches all of them, and somebody who works in one reaches only theirs.
 *
 * Admins get nothing here. The platform administers organizations rather than
 * working inside them, and a space's contents are a tenant's own — see the
 * isolation rule in {@see OrganizationPolicy}. A super admin still passes
 * through the bypass registered in `CoreServiceProvider`.
 */
class SpacePolicy
{
    /**
     * Determine whether the identity can list the organization's spaces.
     *
     * Every member can: the list is how someone finds the spaces they belong
     * to, and it shows them nothing they cannot already reach.
     */
    public function viewAny(User|Admin $identity): bool
    {
        return $identity instanceof User;
    }

    /**
     * Determine whether the identity can open the space.
     */
    public function view(User|Admin $identity, Space $space): bool
    {
        return $identity instanceof User
            && ($this->administers($identity) || $space->accessFor($identity) !== null);
    }

    /**
     * Determine whether the identity can change what is inside the space.
     *
     * What boards, nodes and forms will check before letting anything be
     * written. Viewing is not enough.
     */
    public function edit(User|Admin $identity, Space $space): bool
    {
        return $identity instanceof User
            && ($this->administers($identity) || $space->accessFor($identity)?->allowsEditing() === true);
    }

    /**
     * Determine whether the identity can add a space to the organization.
     */
    public function create(User|Admin $identity): bool
    {
        return $identity instanceof User && $this->administers($identity);
    }

    /**
     * Determine whether the identity can rename or reorder the space.
     */
    public function update(User|Admin $identity, Space $space): bool
    {
        return $identity instanceof User && $this->administers($identity);
    }

    /**
     * Determine whether the identity can remove the space.
     *
     * The default space is refused to everyone, however much they are allowed:
     * removing it would leave members with no space they can reach and work
     * belonging to no process with nowhere to live.
     */
    public function delete(User|Admin $identity, Space $space): bool
    {
        return $identity instanceof User
            && ! $space->isProtected()
            && $this->administers($identity);
    }

    /**
     * Determine whether the identity can add people to the space or change what
     * they may do there.
     */
    public function manageMembers(User|Admin $identity, Space $space): bool
    {
        return $identity instanceof User && $this->administers($identity);
    }

    /**
     * Whether the user holds the capability to administer spaces at all.
     *
     * Scoped to the organization the user is acting in by spatie's team
     * context, so this answers for the active organization and no other.
     */
    private function administers(User $user): bool
    {
        return $user->can(OrganizationPermission::ManageSpaces->value);
    }
}
