<?php

namespace App\Modules\Core\Enums;

/**
 * Every permission available inside an organization. These are created against
 * the `web` guard once, and shared by every organization's roles — only the
 * role assignment is team scoped, never the permission itself.
 */
enum OrganizationPermission: string
{
    case ViewOrganization = 'organization.view';
    case UpdateOrganization = 'organization.update';

    case ViewMembers = 'members.view';
    case ManageMembers = 'members.manage';
    case InviteMembers = 'members.invite';
    case UpdateMembers = 'members.update';
    case RemoveMembers = 'members.remove';

    // Creating, renaming, removing and staffing spaces. Reaching what is inside
    // one is not a permission at all — that is space membership, granted per
    // space, so that administering spaces and working in them stay separate.
    case ManageSpaces = 'spaces.manage';

    case ViewRoles = 'roles.view';
    case CreateRoles = 'roles.create';
    case UpdateRoles = 'roles.update';
    case DeleteRoles = 'roles.delete';

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
}
