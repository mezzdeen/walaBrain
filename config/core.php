<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Email Address Verification
    |--------------------------------------------------------------------------
    |
    | Whether a sign-up checks that the address's domain actually accepts mail,
    | by looking up its MX records. It is the difference between a plausible
    | address and a reachable one, so it is on everywhere it can be.
    |
    | It is a DNS lookup, which means a network call inside a validation run.
    | The test suite turns it off (see phpunit.xml): tests must not depend on
    | name resolution, and a domain's records are not theirs to control.
    |
    */

    'verify_email_dns' => env('CORE_VERIFY_EMAIL_DNS', true),

    /*
    |--------------------------------------------------------------------------
    | Public Record Identifiers
    |--------------------------------------------------------------------------
    |
    | Records are addressed in URLs by a short opaque code rather than by their
    | primary key, so a link does not disclose how many organizations exist, in
    | what order they signed up, or how fast that number is growing.
    |
    | The alphabet is what makes a code unguessable: Sqids' own ordering is
    | public, so leaving it at the default would make every code trivially
    | reversible. This one is shuffled. Treat it as a constant, not a secret to
    | rotate — changing it silently invalidates every link and bookmark ever
    | issued, because a code is derived from the key rather than stored.
    |
    | Five characters addresses roughly fourteen million rows per table, after
    | which codes grow to six on their own. Nothing depends on the width.
    |
    */

    'hash_id' => [
        'alphabet' => env('CORE_HASH_ID_ALPHABET', 'Sn5dJmQ1clPYjfo4xshRzGiFLUeBb7pIgkuvZqEDKHXrt8Vw9y6AOW32aTNMC0'),
        'min_length' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Super Admin
    |--------------------------------------------------------------------------
    |
    | The account the seeder provisions so the admin platform has someone to
    | sign in as. Read through config rather than env() directly, so a cached
    | configuration still honours what the operator set: env() returns its
    | default once `config:cache` has run, and a super admin quietly created
    | with the fallback password would be a back door into every organization.
    |
    | The password has no default on purpose. Production must supply one; local
    | development falls back to a throwaway in the seeder itself.
    |
    */

    'admin' => [
        'email' => env('ADMIN_EMAIL', 'admin@walabrain.com'),
        'name' => env('ADMIN_NAME', 'Admin'),
        'password' => env('ADMIN_PASSWORD'),
    ],

];
