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

];
