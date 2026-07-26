<?php

namespace App\Modules\Core\Enums;

use App\Modules\Core\Support\PermissionTeam;

/**
 * The roles seeded for the super admin platform. They are global — their
 * `organization_id` is null — and assignments sit on the
 * {@see PermissionTeam::SUPER} sentinel team.
 */
enum SuperRole: string
{
    case SuperAdmin = 'super-admin';
    case Support = 'support';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * The permissions granted by the role.
     *
     * @return list<SuperPermission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::SuperAdmin => SuperPermission::cases(),
            self::Support => array_values(array_filter(
                SuperPermission::cases(),
                fn (SuperPermission $permission): bool => $permission->isReadOnly(),
            )),
        };
    }

    /**
     * Whether the role is structural and may not be edited or deleted.
     *
     * Without this the platform can be locked out of itself: delete the only
     * role that grants role management and no one can grant it back.
     */
    public function isProtected(): bool
    {
        return $this === self::SuperAdmin;
    }
}
