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

    'title' => 'الإعدادات العامة',
    'description' => 'حدّد كيف تتصرّف المنصة مع كل من يستخدمها.',
    'updated' => 'تم حفظ الإعدادات.',

    'registration_title' => 'إنشاء الحسابات',
    'registration_description' => 'اختر هل يستطيع أي شخص التسجيل، أم المدعوّون فقط.',

    // The badge reports what the platform is doing right now, which is not the
    // same as the option currently picked in the form.
    'currently_open' => 'الوضع الحالي: مفتوح',
    'currently_closed' => 'الوضع الحالي: مغلق',
    'unsaved' => 'لم يُحفظ بعد.',

    'registration_open' => 'مفتوح',
    'registration_open_help' => 'يستطيع أي شخص إنشاء حساب. عليه تأكيد بريده قبل أن يدخل، وتُنشأ له مؤسسة بمجرد أن يؤكده.',

    'registration_closed' => 'مغلق',
    'registration_closed_help' => 'صفحة التسجيل غير موجودة أصلاً. الدعوة هي المدخل الوحيد، سواء جاءت من المنصة أو من إحدى المؤسسات.',

    'providers_title' => 'منصات تسجيل الدخول',
    'providers_description' => 'الحسابات التي يستطيع الناس التسجيل والدخول بها، إلى جانب البريد وكلمة المرور.',
    'providers_pending' => 'محفوظ لكنه غير مُفعَّل بعد: تظهر أزرار الدخول حين يكتمل الربط مع كل منصة.',
    'providers_not_wired' => 'لم يُربط بعد',

    'providers' => [
        'google' => 'Google',
    ],

];
