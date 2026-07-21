<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Organizations UI Language Lines
    |--------------------------------------------------------------------------
    |
    | Interface strings for the organization management screens on the super
    | admin platform. Shared labels live in `common.php` instead.
    |
    */

    'title' => 'Organizations',
    'description' => 'Manage the companies on the platform.',

    'new' => 'New organization',
    'new_breadcrumb' => 'New',
    'new_description' => 'Add a new company to the platform.',
    'create_action' => 'Create organization',

    'edit_title' => 'Edit organization',
    'edit_breadcrumb' => 'Edit',
    'edit_description' => 'Update the organization details.',
    'edit_page_title' => 'Edit :name',
    'save_changes' => 'Save changes',

    'details_breadcrumb' => 'Details',
    'back' => 'Back',

    'empty_title' => 'No organizations yet',
    'empty_description' => 'Create your first organization to get started.',

    'name' => 'Name',
    'name_placeholder' => 'Acme Inc',
    'color' => 'Organization colour',
    'color_description' => 'This becomes the primary colour of the application for everyone working in this organization.',
    'color_default' => 'Default',
    'color_custom' => 'Custom colour',
    'users' => 'Users',
    'actions' => 'Actions',
    'email' => 'Email',

    'members' => 'Members',
    'member_count_one' => ':count member',
    'member_count_other' => ':count members',
    'no_members' => 'No members linked to this organization yet.',

    'delete_title' => 'Delete organization',
    'delete_description' => 'Are you sure you want to delete :name? This action cannot be undone.',

    'switch' => 'Switch organization',
    'switched' => 'You are now working in :name.',
    'current' => 'Current organization',

    'none_title' => 'No organization yet',
    'none_description' => 'Your account is not linked to an organization. Ask an administrator to invite you, or sign out to use a different account.',

    'created_with_owner' => 'Organization created. :email has been added as its owner.',
    'created_with_invitation' => 'Organization created. An invitation has been sent to :email.',
    'updated' => 'Organization updated.',
    'deleted' => 'Organization deleted.',

    'owner_title' => 'Owner',
    'owner_description' => 'Every organization needs an owner. Start typing an email address to find an existing user, or enter a new one to send an invitation.',
    'owner_email' => 'Owner email',
    'owner_email_placeholder' => 'name@example.com',
    'owner_searching' => 'Searching…',
    'owner_no_results' => 'No account uses this address yet.',
    'owner_will_be_invited' => 'An invitation to own this organization will be sent to this address.',
    'owner_will_be_added' => 'This user will be made the owner as soon as the organization is created.',
    'owner_independent' => 'Their access to any other organization is unaffected.',
    'owner_clear' => 'Choose someone else',

];
