<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Invitation Language Lines
    |--------------------------------------------------------------------------
    |
    | Strings for the ownership emails and the page an invited person lands on
    | to finish setting up their account.
    |
    */

    'greeting' => 'Hello :name,',

    'granted' => [
        'subject' => 'You are now the owner of :name',
        'intro' => 'You have been made the owner of :name.',
        'scope' => 'This is in addition to any other organization you belong to. Nothing about your existing access has changed.',
        'action' => 'Open the dashboard',
    ],

    'invited' => [
        'subject' => 'You have been invited to own :name',
        'intro' => 'You have been invited to join the platform as the owner of :name.',
        'scope' => 'As owner you will have full control over this organization, its members and its roles.',
        'action' => 'Accept the invitation',
        'expires' => 'This invitation expires in :days days.',
        'ignore' => 'If you were not expecting this invitation, you can safely ignore this email.',
    ],

    'title' => 'Finish setting up your account',
    'description' => 'You have been invited to own :name. Choose a name and a password to get started.',
    'email' => 'Email',
    'email_hint' => 'This is the address the invitation was sent to and cannot be changed.',
    'name' => 'Your name',
    'name_placeholder' => 'Full name',
    'password' => 'Password',
    'password_confirmation' => 'Confirm password',
    'submit' => 'Create account',

    'already_registered' => 'An account already exists for this address. Sign in to continue.',

    'invalid_title' => 'This invitation link is not valid',
    'invalid_description' => 'The link may have been mistyped or already withdrawn. Ask whoever invited you to send a new one.',
    'expired_title' => 'This invitation has expired',
    'expired_description' => 'Invitation links are only valid for a limited time. Ask whoever invited you to send a new one.',
    'accepted_title' => 'This invitation has already been used',
    'accepted_description' => 'The account has already been created. Sign in to continue.',
    'back_to_login' => 'Go to sign in',

    // The screen an organization invites its own members from. Kept apart from
    // the `invited.*` lines above, which are worded for ownership specifically.
    'manage' => [
        'title' => 'Invite user',
        'description' => 'Invite someone to join your organization.',
    ],

];
