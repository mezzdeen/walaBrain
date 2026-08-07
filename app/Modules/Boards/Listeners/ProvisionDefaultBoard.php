<?php

namespace App\Modules\Boards\Listeners;

use App\Modules\Boards\Support\Boards;
use App\Modules\Core\Events\OrganizationCreated;

/**
 * Gives every new organization the board it starts with.
 *
 * The whole of what Boards adds to organization creation, and the reason Core
 * announces the event rather than calling into this module: Core provisions
 * what Core owns, and each module furnishes what it owns in response.
 */
class ProvisionDefaultBoard
{
    public function handle(OrganizationCreated $event): void
    {
        Boards::provisionDefault($event->organization);
    }
}
