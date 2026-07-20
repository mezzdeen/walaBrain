<?php

namespace App\Modules\Core\Models;

use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * A permission, discriminated only by guard: `super` permissions belong to the
 * admin platform, `web` permissions to the company platform.
 *
 * Permissions are never team scoped — spatie's `permissions` table has no team
 * column. Every organization's roles link to the same shared `web` permission
 * rows.
 *
 * As with {@see Role}, no `#[Fillable]` attribute: it would break the mass
 * assignment spatie performs through `$guarded = []`.
 *
 * @property int $id
 * @property string $name
 * @property string $guard_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Permission extends SpatiePermission
{
    //
}
