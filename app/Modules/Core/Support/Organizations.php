<?php

namespace App\Modules\Core\Support;

use App\Modules\Core\Models\Organization;
use Illuminate\Database\Eloquent\Collection;

/**
 * Running work across every tenant, from something that belongs to none.
 *
 * A scheduled command is the case this exists for: sending the day's reminders,
 * escalating overdue approvals, releasing expired holds. All of them are
 * per-organization work driven by a process that has no organization of its
 * own, and every one of them would otherwise open by writing the same loop and
 * remembering the same context call.
 */
final class Organizations
{
    /**
     * Run a callback once per organization, with that organization active.
     *
     * The context is set and restored around each one, so a callback that
     * reads or writes tenant-owned records sees exactly that organization's,
     * and one that throws does not leave the next iteration running as the
     * previous tenant.
     *
     * Chunked rather than loaded whole: this runs against every organization on
     * the platform, and the number of them is the one thing here that grows.
     *
     * @param  callable(Organization): void  $callback
     */
    public static function each(callable $callback): void
    {
        Organization::query()
            ->orderBy('id')
            ->chunkById(100, function (Collection $organizations) use ($callback): void {
                foreach ($organizations as $organization) {
                    OrganizationContext::for($organization, fn () => $callback($organization));
                }
            });
    }
}
