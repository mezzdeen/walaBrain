<?php

namespace App\Modules\Core\Http\Requests;

use App\Modules\Core\Models\Permission;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
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
     * The rules a single posted permission name must pass.
     *
     * Two halves. It has to be a permission the platform defines — the caller
     * passes that list, since the two platforms draw from different enums. And
     * it has to be one the actor already holds, which is what keeps a role form
     * from being a ladder: editing a role can only ever hand out abilities its
     * author could already exercise, so holding `roles.update` is not a way to
     * reach the ones it does not itself come with. A super admin holds
     * everything — the gate waves them through every check — so the second half
     * never constrains them.
     *
     * @param  list<string>  $defined
     * @return array<int, ValidationRule|Closure|string>
     */
    protected function permissionRules(array $defined): array
    {
        return [
            Rule::in($defined),
            function (string $attribute, mixed $value, Closure $fail): void {
                if (is_string($value) && ! $this->actorMayGrant($value)) {
                    $fail('core::roles.errors.permission_not_held')->translate();
                }
            },
        ];
    }

    /**
     * Whether the signed-in actor already holds the permission they are trying
     * to put on a role.
     *
     * Asked through the gate rather than the stored role rows, so the
     * super-admin bypass counts and the check is scoped to the active team the
     * way every other permission check on the request is. The guard is named
     * for the same reason it is when resolving the permissions themselves.
     */
    protected function actorMayGrant(string $permission): bool
    {
        return $this->user($this->permissionGuard())?->can($permission) ?? false;
    }

    /**
     * The guard the permissions belong to.
     *
     * Always explicit: spatie resolves an omitted guard from the Permission
     * model, which belongs to no auth provider, and silently lands on `web`.
     */
    abstract protected function permissionGuard(): string;
}
