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

    'settings_description' => 'إدارة ملفك الشخصي وإعدادات حسابك',

    'profile_title' => 'إعدادات الملف الشخصي',
    'profile_description' => 'حدّث اسمك وبريدك الإلكتروني',
    'first_name' => 'الاسم الأول',
    'first_name_placeholder' => 'الاسم الأول',
    'last_name' => 'اسم العائلة',
    'last_name_placeholder' => 'اسم العائلة',
    'email_address' => 'البريد الإلكتروني',
    'email_unverified' => 'لم يتم تأكيد بريدك الإلكتروني.',
    'resend_verification_email' => 'اضغط هنا لإعادة إرسال رسالة التأكيد.',
    'verification_link_sent' => 'تم إرسال رابط تأكيد جديد إلى بريدك الإلكتروني.',

    'security_title' => 'إعدادات الأمان',
    'update_password_title' => 'تغيير كلمة المرور',
    'update_password_description' => 'تأكّد من استخدام كلمة مرور طويلة وعشوائية للحفاظ على أمان حسابك',
    'password' => 'كلمة المرور',
    'current_password' => 'كلمة المرور الحالية',
    'new_password' => 'كلمة المرور الجديدة',
    'confirm_password' => 'تأكيد كلمة المرور',

    'organization_title' => 'إعدادات المؤسسة',
    'organization_description' => 'حدّث بيانات مؤسستك',

    'appearance_title' => 'إعدادات المظهر',
    'appearance_description' => 'حدّث إعدادات المظهر الخاصة بحسابك',

    'delete_account' => 'حذف الحساب',
    'delete_account_description' => 'حذف حسابك وجميع بياناته',
    'warning' => 'تحذير',
    'delete_account_warning' => 'يُرجى التريّث، فلا يمكن التراجع عن هذا الإجراء.',
    'delete_account_confirm_title' => 'هل أنت متأكد من رغبتك في حذف حسابك؟',
    'delete_account_confirm_description' => 'بمجرد حذف حسابك، ستُحذف جميع بياناته ومحتوياته نهائيًا. يُرجى إدخال كلمة المرور لتأكيد رغبتك في حذف الحساب نهائيًا.',

    'passkeys_title' => 'مفاتيح المرور',
    'passkeys_description' => 'إدارة مفاتيح المرور لتسجيل الدخول دون كلمة مرور',
    'passkeys_empty_title' => 'لا توجد مفاتيح مرور بعد',
    'passkeys_empty_description' => 'أضف مفتاح مرور لتسجيل الدخول دون كلمة مرور',
    'passkeys_unsupported' => 'مفاتيح المرور غير مدعومة في هذا المتصفح.',
    'add_passkey' => 'إضافة مفتاح مرور',
    'passkey_name' => 'اسم مفتاح المرور',
    'passkey_name_placeholder' => 'مثال: MacBook Pro أو iPhone',
    'passkey_name_hint' => 'يساعدك الاسم على تمييز هذا المفتاح لاحقًا.',
    'passkey_default_name' => ':browser على :os',
    'register_passkey' => 'تسجيل مفتاح المرور',
    'registering' => 'جارٍ التسجيل...',
    'passkey_added' => 'أُضيف :time',
    'passkey_last_used' => 'آخر استخدام :time',
    'remove' => 'إزالة',
    'remove_passkey' => 'إزالة مفتاح المرور',
    'remove_passkey_confirm' => 'هل أنت متأكد من إزالة مفتاح المرور ":name"؟ لن تتمكن من استخدامه لتسجيل الدخول بعد الآن.',
    'removing' => 'جارٍ الإزالة...',

    'two_factor_title' => 'المصادقة الثنائية',
    'two_factor_description' => 'إدارة إعدادات المصادقة الثنائية لحسابك',
    'two_factor_enabled_description' => 'سيُطلب منك عند تسجيل الدخول إدخال رمز آمن وعشوائي يمكنك الحصول عليه من تطبيق المصادقة الذي يدعم TOTP على هاتفك.',
    'two_factor_disabled_description' => 'عند تفعيل المصادقة الثنائية، سيُطلب منك إدخال رمز آمن عند تسجيل الدخول. ويمكنك الحصول على هذا الرمز من تطبيق مصادقة يدعم TOTP على هاتفك.',
    'enable_two_factor' => 'تفعيل المصادقة الثنائية',
    'disable_two_factor' => 'تعطيل المصادقة الثنائية',
    'continue_setup' => 'متابعة الإعداد',

    'two_factor_enabled_title' => 'تم تفعيل المصادقة الثنائية',
    'two_factor_enabled_modal_description' => 'تم تفعيل المصادقة الثنائية الآن. امسح رمز QR أو أدخل مفتاح الإعداد في تطبيق المصادقة لديك.',
    'enable_two_factor_title' => 'تفعيل المصادقة الثنائية',
    'enable_two_factor_modal_description' => 'لإتمام تفعيل المصادقة الثنائية، امسح رمز QR أو أدخل مفتاح الإعداد في تطبيق المصادقة لديك',
    'verify_code_title' => 'تأكيد رمز المصادقة',
    'verify_code_description' => 'أدخل الرمز المكوّن من 6 أرقام من تطبيق المصادقة',
    'enter_code_manually' => 'أو أدخل الرمز يدويًا',
    'back' => 'رجوع',
    'close' => 'إغلاق',
    'continue' => 'متابعة',

    'recovery_codes_title' => 'رموز استرداد المصادقة الثنائية',
    'recovery_codes_description' => 'تتيح لك رموز الاسترداد استعادة الوصول إلى حسابك إذا فقدت جهاز المصادقة الثنائية. احفظها في مدير كلمات مرور آمن.',
    'view_recovery_codes' => 'عرض رموز الاسترداد',
    'hide_recovery_codes' => 'إخفاء رموز الاسترداد',
    'regenerate_codes' => 'إعادة إنشاء الرموز',
    'recovery_codes_label' => 'رموز الاسترداد',
    'recovery_codes_loading' => 'جارٍ تحميل رموز الاسترداد',
    'recovery_codes_note_before' => 'يمكن استخدام كل رمز استرداد مرة واحدة للوصول إلى حسابك، ويُحذف بعد استخدامه. وإذا احتجت إلى المزيد، اضغط على',
    'recovery_codes_note_after' => 'بالأعلى.',

];
