<?php

namespace App\Modules\Core\Concerns;

use App\Modules\Core\Exceptions\MissingOrganizationContext;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\Scopes\OrganizationScope;
use App\Modules\Core\Support\OrganizationContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Marks a model as owned by one organization, and keeps it that way.
 *
 * Reads are filtered to the active organization by {@see OrganizationScope} and
 * writes are stamped with it, so a controller cannot leak another
 * organization's rows by forgetting a `where`.
 *
 * Not for every table carrying an `organization_id`. Two in this application
 * deliberately opt out:
 *
 * - `organization_invitations` is read by a signed-out visitor holding a token,
 *   before any membership exists. Scoping it would make every invitation
 *   unfindable and break the only route into the application.
 * - `roles` is listed from the admin platform, which has no active organization
 *   by design, and already filters by hand where it matters.
 *
 * Outside HTTP — queued jobs, artisan commands — no middleware has set the
 * context, so wrap the work in {@see OrganizationContext::for()} or reads
 * return nothing and writes throw.
 *
 * @phpstan-require-extends Model
 */
trait BelongsToOrganization
{
    /**
     * Register the scope and the write-time stamp.
     */
    public static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope(new OrganizationScope);

        static::creating(function (Model $model): void {
            // Only when nothing was set. Code that already knows the owner — a
            // seeder, a job, an admin acting on an organization's behalf — must
            // be able to say so, and is unscoped anyway.
            if ($model->getAttribute('organization_id') !== null) {
                return;
            }

            $organization = OrganizationContext::current();

            if ($organization === null) {
                // Loud on purpose. A row with no organization is invisible to
                // every tenant and belongs to none — corruption that would
                // otherwise only surface long after the code that caused it.
                throw MissingOrganizationContext::for($model);
            }

            $model->setAttribute('organization_id', $organization->getKey());
        });
    }

    /**
     * The organization that owns the record.
     *
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Query across every organization.
     *
     * The escape hatch for the admin platform, which administers organizations
     * rather than acting inside one. Every call is a deliberate decision to
     * cross the boundary, which is why it has to be written out.
     *
     * Lifts the filter only. To also move what {@see OrganizationContext} says
     * is active — because the code inside creates records, or checks a role —
     * use {@see OrganizationContext::withoutScoping()} instead.
     *
     * @return Builder<static>
     */
    public static function allOrganizations(): Builder
    {
        return static::query()->withoutGlobalScope(OrganizationScope::class);
    }
}
