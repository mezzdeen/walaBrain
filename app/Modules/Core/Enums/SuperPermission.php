<?php

namespace App\Modules\Core\Enums;

/**
 * Every permission on the super admin platform. These are created against the
 * `super` guard and are never team scoped.
 */
enum SuperPermission: string
{
    case ViewOrganizations = 'organizations.view';
    case CreateOrganizations = 'organizations.create';
    case UpdateOrganizations = 'organizations.update';
    case DeleteOrganizations = 'organizations.delete';
    case ManageOrganizationRoles = 'organizations.roles.manage';

    case ViewAdmins = 'admins.view';
    case CreateAdmins = 'admins.create';
    case UpdateAdmins = 'admins.update';
    case DeleteAdmins = 'admins.delete';

    case ViewRoles = 'roles.view';
    case CreateRoles = 'roles.create';
    case UpdateRoles = 'roles.update';
    case DeleteRoles = 'roles.delete';

    case RunDiagnostics = 'diagnostics.run';

    /**
     * Settling how the platform itself behaves — whether accounts can be
     * created without an invitation, and by which means. There is no matching
     * `.view` case on purpose: the screen is an edit form, so being able to
     * read it and being able to submit it are the same ability.
     */
    case ManagePlatformSettings = 'settings.manage';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * The subject the permission acts on, used to group the permission matrix.
     */
    public function group(): string
    {
        return explode('.', $this->value)[0];
    }

    /**
     * Whether the permission only reads, used to compose read-only roles.
     */
    public function isReadOnly(): bool
    {
        return str_ends_with($this->value, '.view');
    }
}
