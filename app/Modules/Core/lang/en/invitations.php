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

    // The email an organization sends to someone it invites as a member, worded
    // for joining a team rather than owning a tenant.
    'member' => [
        'subject' => 'You have been invited to join :name',
        'intro' => 'You have been invited to join :name.',
        'scope' => 'Open the invitation to accept it. The organization will only appear for you once you do.',
        'action' => 'Accept the invitation',
        'expires' => 'This invitation expires in :days days.',
        'ignore' => 'If you were not expecting this invitation, you can safely ignore this email.',
    ],

    'title' => 'Finish setting up your account',
    'description' => 'You have been invited to join :name. Choose a name and a password to get started.',
    'email' => 'Email',
    'email_hint' => 'This is the address the invitation was sent to and cannot be changed.',
    'first_name' => 'Your first name',
    'first_name_placeholder' => 'First name',
    'last_name' => 'Your last name',
    'last_name_placeholder' => 'Last name',
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
    'wrong_account_title' => 'This invitation is for a different account',
    'wrong_account_description' => 'You are signed in with another address. Sign out and open the link again with the invited address.',
    'back_to_login' => 'Go to sign in',

    // The confirmation an already-registered invitee sees, having followed the
    // link while signed in as the invited account.
    'accept' => [
        'title' => 'Join :name',
        'description' => 'You have been invited to join :name as :role.',
        'submit' => 'Accept and join',
    ],

    // The screen an organization invites its own members from. Kept apart from
    // the `invited.*` lines above, which are worded for ownership specifically.
    'manage' => [
        'title' => 'Invite user',
        'description' => 'Invite someone to join your organization.',
        'email_label' => 'Email address',
        'email_placeholder' => 'name@example.com',
        'searching' => 'Searching',
        'no_results' => 'No account matches that yet.',
        'will_be_invited' => 'They will be sent an invitation.',
        'will_be_added' => 'They already have an account and will be invited to join.',
        'already_member_badge' => 'Already a member',
        'clear' => 'Clear',
        'role_label' => 'Role',
        'role_placeholder' => 'Select a role',
        'submit' => 'Send invitation',
        'pending_title' => 'Pending invitations',
        'pending_empty' => 'No pending invitations.',
        'th_email' => 'Email',
        'th_role' => 'Role',
        'th_expires' => 'Expires',
        'resend' => 'Resend',
        'cancel' => 'Cancel',
        'cancel_title' => 'Withdraw this invitation?',
        'cancel_description' => 'The link sent to :email will stop working. You can invite them again later.',
        'sent' => 'Invitation sent.',
        'cancelled' => 'Invitation withdrawn.',
        'resent' => 'A fresh invitation has been sent.',
    ],

    'errors' => [
        'already_member' => 'This person is already in your organization.',
    ],

];
