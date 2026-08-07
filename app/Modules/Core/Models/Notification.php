<?php

namespace App\Modules\Core\Models;

use App\Modules\Core\Concerns\BelongsToOrganization;
use App\Modules\Core\Support\OrganizationContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;

/**
 * One in-app notification, as it appears in somebody's notification centre.
 *
 * Extends the framework's own so the database channel keeps working untouched,
 * and adds the one thing a multi-tenant platform needs it to know: which
 * organization it is about.
 *
 * Deliberately not carrying {@see BelongsToOrganization}. That trait refuses to
 * write a record with no organization, which is right for a board or a node and
 * wrong here: plenty of notifications belong to a person rather than to a
 * business line — an admin's mail diagnostic, anything about somebody's own
 * account before they are in an organization at all. Those are stamped null on
 * purpose, and {@see self::visibleIn()} is what keeps the rest apart.
 *
 * @property string $id
 * @property int|null $organization_id
 * @property array<string, mixed> $data
 * @property Carbon|null $read_at
 */
class Notification extends DatabaseNotification
{
    /**
     * Stamp the notification with the organization it was raised in.
     *
     * Taken from the active context rather than asked for, so a notification
     * sent from inside a request or a queued job lands in the right business
     * line without every notification class having to say so. Null when there
     * is no organization, which is a real answer here rather than a mistake.
     */
    protected static function booted(): void
    {
        static::creating(function (self $notification): void {
            if ($notification->organization_id !== null) {
                return;
            }

            $notification->organization_id = OrganizationContext::current()?->getKey();
        });
    }

    /**
     * Constrain to the notifications someone should see while working in the
     * given organization.
     *
     * Those about that organization's work, plus those about the person
     * themselves, which belong to no organization and would otherwise be
     * invisible from everywhere.
     *
     * @param  Builder<Notification>  $query
     * @return Builder<Notification>
     */
    public function scopeVisibleIn(Builder $query, ?Organization $organization): Builder
    {
        return $query->where(function (Builder $builder) use ($organization): void {
            $builder->whereNull('organization_id');

            if ($organization instanceof Organization) {
                $builder->orWhere('organization_id', $organization->getKey());
            }
        });
    }
}
