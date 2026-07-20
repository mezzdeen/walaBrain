<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Diagnostics Language Lines
    |--------------------------------------------------------------------------
    |
    | Strings for the self-check tools on the super admin platform. These are
    | read by administrators verifying infrastructure, not by end users.
    |
    */

    'mail' => [
        'subject' => 'Mail delivery check — :app',
        'intro' => 'This message confirms that :app can deliver email.',
        'requested_at' => 'Requested at :at (UTC).',
        'transport' => 'Sent through the ":mailer" mailer via :host on port :port.',
        'from' => 'From address: :from',
        'ignore' => 'If you did not request this check, you can safely ignore this message.',
    ],

];
