<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;

/**
 * What creating an organization settled.
 *
 * The owner is only present when the named address already belonged to an
 * account; a null owner means an invitation went out instead. Callers need that
 * distinction to say what actually happened, and re-deriving it would mean
 * asking the database something the creation already knew.
 */
final readonly class OrganizationCreationResult
{
    public function __construct(
        public Organization $organization,
        public ?User $owner,
    ) {}

    /**
     * Whether an existing account was made owner outright.
     */
    public function ownerWasAttached(): bool
    {
        return $this->owner instanceof User;
    }
}
