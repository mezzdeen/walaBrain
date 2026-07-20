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
        'subject' => 'فحص إرسال البريد — :app',
        'intro' => 'تؤكّد هذه الرسالة أن :app قادر على إرسال البريد.',
        'requested_at' => 'طُلب الفحص في :at (بتوقيت UTC).',
        'transport' => 'أُرسلت عبر مُرسِل ":mailer" من خلال :host على المنفذ :port.',
        'from' => 'عنوان المُرسِل: :from',
        'ignore' => 'إن لم تطلب هذا الفحص، يمكنك تجاهل هذه الرسالة بأمان.',
    ],

];
