<?php

namespace App\Modules\Core\Http\Requests;

use App\Modules\Core\Models\Permission;
use Spatie\Permission\Contracts\Permission as PermissionContract;

/**
 * Turns the permission names posted by a role form into permission models.
 *
 * Shared by the role requests of both platforms; only the guard differs.
 */
trait ResolvesPermissions
{
    /**
     * The permission models selected on the form.
     *
     * @return list<PermissionContract>
     */
    public function permissions(): array
    {
        return array_values(array_map(
            fn (string $name): PermissionContract => Permission::findOrCreate($name, $this->permissionGuard()),
            $this->validated('permissions', []),
        ));
    }

    /**
     * The guard the permissions belong to.
     *
     * Always explicit: spatie resolves an omitted guard from the Permission
     * model, which belongs to no auth provider, and silently lands on `web`.
     */
    abstract protected function permissionGuard(): string;
}
