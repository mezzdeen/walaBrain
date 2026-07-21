<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Platform Settings Language Lines
    |--------------------------------------------------------------------------
    |
    | Strings for the super platform's general settings screen: the switch that
    | opens or closes self-registration, and the login providers that become
    | available once it is open.
    |
    */

    'title' => 'General settings',
    'description' => 'Settle how the platform behaves for everyone who uses it.',
    'updated' => 'Settings saved.',

    'registration_title' => 'Creating an account',
    'registration_description' => 'Decide whether anyone may sign up, or only people who were invited.',

    // The badge reports what the platform is doing right now, which is not the
    // same as the option currently picked in the form.
    'currently_open' => 'Currently open',
    'currently_closed' => 'Currently closed',
    'unsaved' => 'Not saved yet.',

    'registration_open' => 'Open',
    'registration_open_help' => 'Anyone may create an account. They must confirm their email address before they can sign in, and an organization is created for them once they do.',

    'registration_closed' => 'Closed',
    'registration_closed_help' => 'The sign-up page does not exist. An invitation is the only way in, whether it comes from the platform or from an organization.',

    'providers_title' => 'Login providers',
    'providers_description' => 'Which accounts people may sign up and sign in with, alongside an email address and password.',
    'providers_pending' => 'Saved, but not yet in use: the sign-in buttons appear once the connection to each provider is built.',
    'providers_not_wired' => 'Not connected yet',

    'providers' => [
        'google' => 'Google',
    ],

];
