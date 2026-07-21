<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Settings UI Language Lines
    |--------------------------------------------------------------------------
    |
    | Interface strings for the account settings screens owned by the Core
    | module: profile, security, two-factor authentication, passkeys and
    | appearance. Shared labels stay in `common.php`.
    |
    */

    'settings_description' => 'Manage your profile and account settings',

    'profile_title' => 'Profile settings',
    'profile_description' => 'Update your name and email address',
    'first_name' => 'First name',
    'first_name_placeholder' => 'First name',
    'last_name' => 'Last name',
    'last_name_placeholder' => 'Last name',
    'email_address' => 'Email address',
    'email_unverified' => 'Your email address is unverified.',
    'resend_verification_email' => 'Click here to re-send the verification email.',
    'verification_link_sent' => 'A new verification link has been sent to your email address.',

    'security_title' => 'Security settings',
    'update_password_title' => 'Update password',
    'update_password_description' => 'Ensure your account is using a long, random password to stay secure',
    'password' => 'Password',
    'current_password' => 'Current password',
    'new_password' => 'New password',
    'confirm_password' => 'Confirm password',

    'organization_title' => 'Organization settings',
    'organization_description' => "Update your organization's information",

    'appearance_title' => 'Appearance settings',
    'appearance_description' => 'Update the appearance settings for your account',

    'delete_account' => 'Delete account',
    'delete_account_description' => 'Delete your account and all of its resources',
    'warning' => 'Warning',
    'delete_account_warning' => 'Please proceed with caution, this cannot be undone.',
    'delete_account_confirm_title' => 'Are you sure you want to delete your account?',
    'delete_account_confirm_description' => 'Once your account is deleted, all of its resources and data will also be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.',

    'passkeys_title' => 'Passkeys',
    'passkeys_description' => 'Manage your passkeys for passwordless sign-in',
    'passkeys_empty_title' => 'No passkeys yet',
    'passkeys_empty_description' => 'Add a passkey to sign in without a password',
    'passkeys_unsupported' => 'Passkeys are not supported in this browser.',
    'add_passkey' => 'Add passkey',
    'passkey_name' => 'Passkey name',
    'passkey_name_placeholder' => 'e.g., MacBook Pro, iPhone',
    'passkey_name_hint' => 'A name helps you identify this passkey later.',
    'passkey_default_name' => ':browser on :os',
    'register_passkey' => 'Register passkey',
    'registering' => 'Registering...',
    'passkey_added' => 'Added :time',
    'passkey_last_used' => 'Last used :time',
    'remove' => 'Remove',
    'remove_passkey' => 'Remove passkey',
    'remove_passkey_confirm' => 'Are you sure you want to remove the ":name" passkey? You will no longer be able to use it to sign in.',
    'removing' => 'Removing...',

    'two_factor_title' => 'Two-factor authentication',
    'two_factor_description' => 'Manage your two-factor authentication settings',
    'two_factor_enabled_description' => 'You will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone.',
    'two_factor_disabled_description' => 'When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.',
    'enable_two_factor' => 'Enable 2FA',
    'disable_two_factor' => 'Disable 2FA',
    'continue_setup' => 'Continue setup',

    'two_factor_enabled_title' => 'Two-factor authentication enabled',
    'two_factor_enabled_modal_description' => 'Two-factor authentication is now enabled. Scan the QR code or enter the setup key in your authenticator app.',
    'enable_two_factor_title' => 'Enable two-factor authentication',
    'enable_two_factor_modal_description' => 'To finish enabling two-factor authentication, scan the QR code or enter the setup key in your authenticator app',
    'verify_code_title' => 'Verify authentication code',
    'verify_code_description' => 'Enter the 6-digit code from your authenticator app',
    'enter_code_manually' => 'or, enter the code manually',
    'back' => 'Back',
    'close' => 'Close',
    'continue' => 'Continue',

    'recovery_codes_title' => '2FA recovery codes',
    'recovery_codes_description' => 'Recovery codes let you regain access if you lose your 2FA device. Store them in a secure password manager.',
    'view_recovery_codes' => 'View recovery codes',
    'hide_recovery_codes' => 'Hide recovery codes',
    'regenerate_codes' => 'Regenerate codes',
    'recovery_codes_label' => 'Recovery codes',
    'recovery_codes_loading' => 'Loading recovery codes',
    'recovery_codes_note_before' => 'Each recovery code can be used once to access your account and will be removed after use. If you need more, click',
    'recovery_codes_note_after' => 'above.',

];
