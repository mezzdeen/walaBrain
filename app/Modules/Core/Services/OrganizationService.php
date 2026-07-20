<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Models\Admin;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;
use App\Modules\Core\Notifications\OrganizationOwnershipGranted;
use App\Modules\Core\Support\OrganizationInvitations;
use App\Modules\Core\Support\OrganizationOwners;
use App\Modules\Core\Support\OrganizationRoles;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * What can be done to an organization, independent of who is asking.
 *
 * Composes the building blocks in `Support` — roles, ownership, invitations —
 * into the whole operations the application actually performs, so a controller
 * is left with nothing but turning a request into a response.
 */
final class OrganizationService
{
    /**
     * Every organization, newest first, with its member count.
     *
     * @return Collection<int, Organization>
     */
    public function listing(): Collection
    {
        return Organization::query()
            ->withCount('users')
            ->latest()
            ->get(['id', 'name', 'created_at']);
    }

    /**
     * The organization's members.
     *
     * @return Collection<int, User>
     */
    public function members(Organization $organization): Collection
    {
        return $organization->users()->get(['users.id', 'name', 'email']);
    }

    /**
     * Create an organization, provision its roles, and settle its ownership.
     *
     * The owner is looked up before the transaction opens because the caller
     * needs to know which of the two outcomes happened, and the lookup is a read
     * that has no business holding a write transaction open.
     */
    public function create(string $name, string $ownerEmail, ?Admin $invitedBy = null): OrganizationCreationResult
    {
        $owner = User::query()->firstWhere('email', $ownerEmail);

        // An organization without its roles cannot be administered, and one
        // without an owner cannot be reached at all, so all of it either happens
        // together or not at all.
        $organization = DB::transaction(function () use ($name, $ownerEmail, $owner, $invitedBy): Organization {
            $organization = Organization::create(['name' => $name]);
            OrganizationRoles::provision($organization);

            if ($owner instanceof User) {
                OrganizationOwners::assign($organization, $owner);
                $owner->notify(new OrganizationOwnershipGranted($organization));
            } else {
                OrganizationInvitations::issue($organization, $ownerEmail, $invitedBy);
            }

            return $organization;
        });

        return new OrganizationCreationResult($organization, $owner);
    }

    /**
     * Rename the organization.
     */
    public function update(Organization $organization, string $name): void
    {
        $organization->update(['name' => $name]);
    }

    /**
     * Delete the organization.
     *
     * Its memberships, roles and pending invitations go with it on the database
     * side, through the cascades declared in their migrations.
     */
    public function delete(Organization $organization): void
    {
        $organization->delete();
    }
}
