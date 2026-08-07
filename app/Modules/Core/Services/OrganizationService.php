<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Enums\OrganizationRole;
use App\Modules\Core\Events\OrganizationCreated;
use App\Modules\Core\Models\Admin;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;
use App\Modules\Core\Notifications\OrganizationOwnershipGranted;
use App\Modules\Core\Support\Locale;
use App\Modules\Core\Support\OrganizationContext;
use App\Modules\Core\Support\OrganizationInvitations;
use App\Modules\Core\Support\OrganizationOwners;
use App\Modules\Core\Support\OrganizationRoles;
use App\Modules\Core\Support\Spaces;
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
        // `id` stays in the column list: it is what `hash_id` is derived from,
        // and a row loaded without it has no address to link to.
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
        // `users.id` stays in the column list: it is what `hash_id` is derived
        // from, and a row loaded without it has no address to link to.
        return $organization->users()->get(['users.id', 'first_name', 'last_name', 'email']);
    }

    /**
     * Create an organization, provision its roles and its default space, and
     * settle its ownership.
     *
     * The owner is looked up before the transaction opens because the caller
     * needs to know which of the two outcomes happened, and the lookup is a read
     * that has no business holding a write transaction open.
     */
    public function create(string $name, string $ownerEmail, ?Admin $invitedBy = null, ?string $locale = null): OrganizationCreationResult
    {
        $locale ??= Locale::default();

        $owner = User::query()->firstWhere('email', $ownerEmail);

        // An organization without its roles cannot be administered, and one
        // without an owner cannot be reached at all, so all of it either happens
        // together or not at all.
        $organization = DB::transaction(function () use ($name, $ownerEmail, $owner, $invitedBy, $locale): Organization {
            if ($owner instanceof User) {
                $organization = $this->createForOwner($owner, $name, $locale);
                $owner->notify(new OrganizationOwnershipGranted($organization));

                return $organization;
            }

            $organization = Organization::create(['name' => $name, 'locale' => $locale]);
            OrganizationRoles::provision($organization);
            Spaces::provisionDefault($organization);
            OrganizationInvitations::issue($organization, $ownerEmail, OrganizationRole::Owner->value, $invitedBy);

            OrganizationCreated::dispatch($organization);

            return $organization;
        });

        return new OrganizationCreationResult($organization, $owner);
    }

    /**
     * Create an organization that already has its owner.
     *
     * The other half of {@see self::create()}, and the whole of what someone
     * who signs themselves up needs: there is nobody to invite and nobody to
     * notify, because the owner is the person waiting on the response.
     *
     * Callers outside a request have no organization context, and `Role` is
     * scoped by one — see {@see OrganizationContext}
     * for what that costs if it is skipped.
     */
    public function createForOwner(User $owner, string $name, ?string $locale = null): Organization
    {
        return DB::transaction(function () use ($owner, $name, $locale): Organization {
            $organization = Organization::create([
                'name' => $name,
                // Someone signing themselves up is the organization's first
                // author, so their own language is the best guess at the one it
                // will write in. They can change it.
                'locale' => $locale ?? $owner->preferredLocale() ?? Locale::default(),
            ]);

            OrganizationRoles::provision($organization);
            Spaces::provisionDefault($organization);
            OrganizationOwners::assign($organization, $owner);

            // Announced once everything Core owns is in place, so a listener
            // furnishing the organization finds a space to put things in.
            OrganizationCreated::dispatch($organization);

            return $organization;
        });
    }

    /**
     * Rename the organization.
     */
    public function update(Organization $organization, string $name): void
    {
        $organization->update(['name' => $name]);
    }

    /**
     * Soft delete the organization.
     *
     * The row stays, so the organization can be brought back with its
     * memberships and roles intact. Its pending invitations are discarded on
     * the way out — see {@see Organization::booted()}.
     */
    public function delete(Organization $organization): void
    {
        $organization->delete();
    }
}
