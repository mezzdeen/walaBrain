<?php

namespace App\Modules\Core\Exceptions;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Thrown when an organization-owned model is queried by a request that is
 * confined to an organization but has none.
 */
final class MissingOrganizationContext extends RuntimeException
{
    public static function for(Model $model): self
    {
        return new self(sprintf(
            'Cannot query [%s] without an active organization. Add the `organization` middleware to the route, or wrap the call in OrganizationContext::for() or ::withoutScoping().',
            $model::class,
        ));
    }
}
