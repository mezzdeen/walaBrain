<?php

namespace App\Modules\Core\Models;

use App\Modules\Core\Concerns\HasHashId;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * A role, owned either by an organization (`web` guard) or by the platform
 * itself (`super` guard, null organization).
 *
 * Unlike every other model in this module, this one carries no `#[Fillable]`
 * attribute on purpose. Spatie mass-assigns roles through `$guarded = []` with
 * an empty `$fillable`, and Laravel merges the attribute's columns into
 * `$fillable`. Declaring it here would silently drop `guard_name` and
 * `organization_id` from `Role::create()` — the role would be created, just not
 * the one that was asked for.
 *
 * @property int $id
 * @property int|null $organization_id
 * @property string $name
 * @property string $guard_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Role extends SpatieRole
{
    // Safe alongside the `#[Fillable]` warning above: this adds an appended
    // attribute and a hidden one, neither of which feeds `$fillable`. The
    // permission package reads roles through `getAttributes()`, which sees
    // neither, so its cache is unaffected.
    use HasHashId;

    /**
     * The organization that owns the role, or null when the role is global.
     *
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
