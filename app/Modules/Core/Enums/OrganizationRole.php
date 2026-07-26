<?php

namespace App\Modules\Core\Enums;

/**
 * The roles every organization starts with. Each organization owns its own
 * copies — same names, different rows — so an organization can edit what its
 * roles grant without affecting anyone else, and can add roles of its own.
 */
enum OrganizationRole: string
{
    case Owner = 'owner';
    case Member = 'member';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * The permissions the role is provisioned with.
     *
     * @return list<OrganizationPermission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Owner => OrganizationPermission::cases(),
            self::Member => [
                OrganizationPermission::ViewOrganization,
                OrganizationPermission::ViewMembers,
            ],
        };
    }

    /**
     * Whether the role is structural and may not be deleted from an
     * organization. Losing the owner role would leave an organization with no
     * way to administer itself.
     */
    public function isProtected(): bool
    {
        return $this === self::Owner;
    }
}
