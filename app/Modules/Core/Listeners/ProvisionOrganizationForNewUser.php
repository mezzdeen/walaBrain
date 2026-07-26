<?php

namespace App\Modules\Core\Listeners;

use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;
use App\Modules\Core\Services\OrganizationService;
use App\Modules\Core\Support\OrganizationContext;
use Illuminate\Auth\Events\Verified;

/**
 * Gives someone who signed themselves up an organization to work in, the moment
 * they confirm their address.
 *
 * Hung off confirmation rather than off registration on purpose. An account
 * whose address is never confirmed can never be signed into, so provisioning at
 * sign-up would leave an organization behind for every abandoned attempt and
 * every address typed wrong.
 */
class ProvisionOrganizationForNewUser
{
    public function __construct(private readonly OrganizationService $organizations) {}

    /**
     * Handle the event.
     */
    public function handle(Verified $event): void
    {
        $user = $event->user;

        // Everyone who arrived by invitation already has one, and the platform
        // may hand somebody a second organization later. Neither should get an
        // extra one for confirming an address.
        if (! $user instanceof User || $user->organizations()->exists()) {
            return;
        }

        // The request that carried the confirmation link resolved no
        // organization — the user had none — so it is scoped to null, and
        // `Role` is filtered by that scope. Provisioning the new organization's
        // roles inside it would read and write against the wrong tenant.
        OrganizationContext::withoutScoping(function () use ($user): Organization {
            return $this->organizations->createForOwner($user, $this->nameFor($user));
        });
    }

    /**
     * The name the new organization starts life with.
     *
     * Named after its owner rather than given something anonymous, so it reads
     * as theirs on the very first screen. It is a starting point, not a
     * decision: renaming it is the first thing the organization settings offer.
     */
    private function nameFor(User $user): string
    {
        return __('core::organizations.default_name', ['name' => $user->first_name]);
    }
}
