<?php

namespace App\Modules\Core\Models\Scopes;

use App\Modules\Core\Concerns\BelongsToOrganization;
use App\Modules\Core\Exceptions\MissingOrganizationContext;
use App\Modules\Core\Support\OrganizationContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Confine every query to the organization the request is acting on.
 *
 * Applied by {@see BelongsToOrganization}. The point is that forgetting the
 * filter is not an option: a missed `where` on a tenant table shows one
 * customer another customer's records, which is the worst failure this
 * application can have.
 *
 * @implements Scope<Model>
 */
final class OrganizationScope implements Scope
{
    /**
     * @param  Builder<covariant Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (! OrganizationContext::isScoped()) {
            return;
        }

        $organization = OrganizationContext::current();

        if ($organization === null) {
            // Loud rather than empty. A scoped context with no organization is
            // a wiring mistake, and returning no rows would render as a working
            // page that happens to be empty — the bug would ship. The
            // `organization` middleware makes this unreachable on every tenant
            // route, so it only fires when a route forgot to declare itself.
            throw MissingOrganizationContext::for($model);
        }

        $builder->where(
            $model->qualifyColumn('organization_id'),
            $organization->getKey(),
        );
    }
}
