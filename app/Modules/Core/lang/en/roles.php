<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Roles UI Language Lines
    |--------------------------------------------------------------------------
    |
    | Interface strings for the role and permission management screens on both
    | platforms. Shared labels live in `common.php` instead.
    |
    */

    'title' => 'Roles',
    'description' => 'Control what each role on the platform is allowed to do.',

    'new' => 'New role',
    'new_breadcrumb' => 'New',
    'new_description' => 'Define a role and the permissions it grants.',
    'create_action' => 'Create role',

    'edit_title' => 'Edit role',
    'edit_breadcrumb' => 'Edit',
    'edit_description' => 'Update the role and the permissions it grants.',
    'edit_page_title' => 'Edit :name',
    'save_changes' => 'Save changes',

    'empty_title' => 'No roles yet',
    'empty_description' => 'Create your first role to get started.',

    'name' => 'Name',
    'name_placeholder' => 'Support',
    'actions' => 'Actions',
    'assigned' => 'Assigned to',
    'assigned_count_one' => ':count admin',
    'assigned_count_other' => ':count admins',

    'permissions' => 'Permissions',
    'permissions_description' => 'Pick what this role is allowed to do.',
    'permission_count_one' => ':count permission',
    'permission_count_other' => ':count permissions',
    'select_all' => 'Select all',

    'protected' => 'Protected',
    'protected_description' => 'This role can not be changed or deleted, so the platform can always be administered.',

    'delete_title' => 'Delete role',
    'delete_description' => 'Are you sure you want to delete :name? Admins holding it will lose its permissions.',

    'created' => 'Role created.',
    'updated' => 'Role updated.',
    'deleted' => 'Role deleted.',
    'member_updated' => 'Member roles updated.',

    'organization_title' => 'Roles in :name',
    'organization_description' => 'The roles this organization owns, and who holds them.',
    'organization_breadcrumb' => 'Roles',
    'manage_roles' => 'Manage roles',
    'members_title' => 'Members',
    'members_description' => 'Choose which roles each member holds in this organization.',
    'no_roles' => 'No roles',
    'owned_roles' => 'Owned roles',

    'groups' => [
        'organizations' => 'Organizations',
        'admins' => 'Admins',
        'roles' => 'Roles',
        'organization' => 'Organization',
        'members' => 'Members',
        'processes' => 'Processes',
        'tasks' => 'Tasks',
        'spaces' => 'Spaces',
        'diagnostics' => 'Diagnostics',
        'settings' => 'Platform settings',
    ],

    /*
    | Keyed by everything after the group, so a permission renders as its group
    | heading plus the ability below it rather than needing a line of its own.
    | Nested, not dotted: lookups walk the key one segment at a time, so a
    | literal 'members.manage' key could never be reached.
    */
    'abilities' => [
        'assign' => 'Assign',
        'design' => 'Design',
        'view' => 'View',
        'create' => 'Create',
        'update' => 'Update',
        'delete' => 'Delete',
        'invite' => 'Invite',
        'remove' => 'Remove',
        'run' => 'Run',
        'manage' => 'Manage',
        'roles' => [
            'manage' => 'Manage roles',
        ],
    ],

    'errors' => [
        'permission_not_held' => 'You can only grant permissions you hold yourself.',
    ],

];
