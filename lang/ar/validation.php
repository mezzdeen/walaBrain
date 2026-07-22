<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Keys that are not translated here fall back to the
    | `fallback_locale` (English) automatically.
    |
    */

    'accepted' => 'يجب قبول :attribute.',
    'accepted_if' => 'يجب قبول :attribute عندما تكون قيمة :other مساوية لـ :value.',
    'active_url' => 'حقل :attribute ليس رابطًا صحيحًا.',
    'after' => 'يجب أن يكون :attribute تاريخًا بعد :date.',
    'after_or_equal' => 'يجب أن يكون :attribute تاريخًا بعد أو يساوي :date.',
    'alpha' => 'يجب أن يحتوي :attribute على أحرف فقط.',
    'alpha_dash' => 'يجب أن يحتوي :attribute على أحرف وأرقام وشرطات فقط.',
    'alpha_num' => 'يجب أن يحتوي :attribute على أحرف وأرقام فقط.',
    'any_of' => 'قيمة :attribute غير صحيحة.',
    'array' => 'يجب أن يكون :attribute مصفوفة.',
    'ascii' => 'يجب أن يحتوي :attribute على أحرف وأرقام ورموز أحادية البايت فقط.',
    'before' => 'يجب أن يكون :attribute تاريخًا قبل :date.',
    'before_or_equal' => 'يجب أن يكون :attribute تاريخًا قبل أو يساوي :date.',
    'between' => [
        'array' => 'يجب أن يحتوي :attribute على عدد عناصر بين :min و :max.',
        'file' => 'يجب أن يكون حجم :attribute بين :min و :max كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute بين :min و :max.',
        'string' => 'يجب أن يكون طول :attribute بين :min و :max حرفًا.',
    ],
    'boolean' => 'يجب أن تكون قيمة :attribute إما صحيحة أو خاطئة.',
    'can' => 'يحتوي :attribute على قيمة غير مصرّح بها.',
    'confirmed' => 'حقل تأكيد :attribute غير مطابق.',
    'contains' => 'يفتقد :attribute إلى قيمة مطلوبة.',
    'current_password' => 'كلمة المرور غير صحيحة.',
    'date' => 'حقل :attribute ليس تاريخًا صحيحًا.',
    'date_equals' => 'يجب أن يكون :attribute تاريخًا يساوي :date.',
    'date_format' => 'حقل :attribute لا يطابق الصيغة :format.',
    'decimal' => 'يجب أن يحتوي :attribute على :decimal منزلة عشرية.',
    'declined' => 'يجب رفض :attribute.',
    'declined_if' => 'يجب رفض :attribute عندما تكون قيمة :other مساوية لـ :value.',
    'different' => 'يجب أن يكون :attribute مختلفًا عن :other.',
    'digits' => 'يجب أن يحتوي :attribute على :digits رقمًا.',
    'digits_between' => 'يجب أن يحتوي :attribute على عدد أرقام بين :min و :max.',
    'dimensions' => 'أبعاد صورة :attribute غير صحيحة.',
    'distinct' => 'يحتوي :attribute على قيمة مكررة.',
    'doesnt_contain' => 'يجب ألا يحتوي :attribute على أيٍّ من القيم التالية: :values.',
    'doesnt_end_with' => 'يجب ألا ينتهي :attribute بأحد القيم التالية: :values.',
    'doesnt_start_with' => 'يجب ألا يبدأ :attribute بأحد القيم التالية: :values.',
    'email' => 'يجب أن يكون :attribute بريدًا إلكترونيًا صحيحًا.',
    'encoding' => 'يجب أن يكون ترميز :attribute بصيغة :encoding.',
    'ends_with' => 'يجب أن ينتهي :attribute بأحد القيم التالية: :values.',
    'enum' => 'قيمة :attribute المحددة غير صحيحة.',
    'exists' => 'قيمة :attribute المحددة غير صحيحة.',
    'extensions' => 'يجب أن يكون امتداد :attribute أحد الامتدادات التالية: :values.',
    'file' => 'يجب أن يكون :attribute ملفًا.',
    'filled' => 'حقل :attribute مطلوب.',
    'gt' => [
        'array' => 'يجب أن يحتوي :attribute على أكثر من :value عنصرًا.',
        'file' => 'يجب أن يكون حجم :attribute أكبر من :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute أكبر من :value.',
        'string' => 'يجب أن يكون طول :attribute أكبر من :value حرفًا.',
    ],
    'gte' => [
        'array' => 'يجب أن يحتوي :attribute على :value عنصرًا أو أكثر.',
        'file' => 'يجب أن يكون حجم :attribute أكبر من أو يساوي :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute أكبر من أو تساوي :value.',
        'string' => 'يجب أن يكون طول :attribute أكبر من أو يساوي :value حرفًا.',
    ],
    'hex_color' => 'يجب أن يكون :attribute لونًا سداسيًا صالحًا.',
    'image' => 'يجب أن يكون :attribute صورة.',
    'in' => 'قيمة :attribute المحددة غير صحيحة.',
    'in_array' => 'يجب أن تكون قيمة :attribute موجودة في :other.',
    'in_array_keys' => 'يجب أن يحتوي :attribute على واحد على الأقل من المفاتيح التالية: :values.',
    'integer' => 'يجب أن يكون :attribute عددًا صحيحًا.',
    'ip' => 'يجب أن يكون :attribute عنوان IP صحيحًا.',
    'ipv4' => 'يجب أن يكون :attribute عنوان IPv4 صحيحًا.',
    'ipv6' => 'يجب أن يكون :attribute عنوان IPv6 صحيحًا.',
    'json' => 'يجب أن يكون :attribute نص JSON صحيحًا.',
    'list' => 'يجب أن يكون :attribute قائمة.',
    'lowercase' => 'يجب أن يكون :attribute بأحرف صغيرة.',
    'lt' => [
        'array' => 'يجب أن يحتوي :attribute على أقل من :value عنصرًا.',
        'file' => 'يجب أن يكون حجم :attribute أقل من :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute أقل من :value.',
        'string' => 'يجب أن يكون طول :attribute أقل من :value حرفًا.',
    ],
    'lte' => [
        'array' => 'يجب ألا يحتوي :attribute على أكثر من :value عنصرًا.',
        'file' => 'يجب أن يكون حجم :attribute أقل من أو يساوي :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute أقل من أو تساوي :value.',
        'string' => 'يجب أن يكون طول :attribute أقل من أو يساوي :value حرفًا.',
    ],
    'mac_address' => 'يجب أن يكون :attribute عنوان MAC صحيحًا.',
    'max' => [
        'array' => 'يجب ألا يحتوي :attribute على أكثر من :max عنصرًا.',
        'file' => 'يجب ألا يزيد حجم :attribute عن :max كيلوبايت.',
        'numeric' => 'يجب ألا تزيد قيمة :attribute عن :max.',
        'string' => 'يجب ألا يزيد طول :attribute عن :max حرفًا.',
    ],
    'max_digits' => 'يجب ألا يحتوي :attribute على أكثر من :max رقمًا.',
    'mimes' => 'يجب أن يكون :attribute ملفًا من النوع: :values.',
    'mimetypes' => 'يجب أن يكون :attribute ملفًا من النوع: :values.',
    'min' => [
        'array' => 'يجب أن يحتوي :attribute على :min عنصرًا على الأقل.',
        'file' => 'يجب ألا يقل حجم :attribute عن :min كيلوبايت.',
        'numeric' => 'يجب ألا تقل قيمة :attribute عن :min.',
        'string' => 'يجب ألا يقل طول :attribute عن :min حرفًا.',
    ],
    'min_digits' => 'يجب أن يحتوي :attribute على :min رقمًا على الأقل.',
    'missing' => 'يجب أن يكون حقل :attribute غير موجود.',
    'missing_if' => 'يجب أن يكون حقل :attribute غير موجود عندما تكون قيمة :other مساوية لـ :value.',
    'missing_unless' => 'يجب أن يكون حقل :attribute غير موجود ما لم تكن قيمة :other مساوية لـ :value.',
    'missing_with' => 'يجب أن يكون حقل :attribute غير موجود عند وجود :values.',
    'missing_with_all' => 'يجب أن يكون حقل :attribute غير موجود عند وجود :values.',
    'multiple_of' => 'يجب أن تكون قيمة :attribute من مضاعفات :value.',
    'not_in' => 'قيمة :attribute المحددة غير صحيحة.',
    'not_regex' => 'صيغة :attribute غير صحيحة.',
    'numeric' => 'يجب أن يكون :attribute رقمًا.',
    'password' => [
        'letters' => 'يجب أن تحتوي :attribute على حرف واحد على الأقل.',
        'mixed' => 'يجب أن تحتوي :attribute على حرف كبير وحرف صغير على الأقل.',
        'numbers' => 'يجب أن تحتوي :attribute على رقم واحد على الأقل.',
        'symbols' => 'يجب أن تحتوي :attribute على رمز واحد على الأقل.',
        'uncompromised' => 'ظهرت :attribute في تسريب بيانات. يرجى اختيار قيمة مختلفة.',
    ],
    'present' => 'يجب أن يكون حقل :attribute موجودًا.',
    'present_if' => 'يجب أن يكون حقل :attribute موجودًا عندما تكون قيمة :other مساوية لـ :value.',
    'present_unless' => 'يجب أن يكون حقل :attribute موجودًا ما لم تكن قيمة :other مساوية لـ :value.',
    'present_with' => 'يجب أن يكون حقل :attribute موجودًا عند وجود :values.',
    'present_with_all' => 'يجب أن يكون حقل :attribute موجودًا عند وجود :values.',
    'prohibited' => 'حقل :attribute محظور.',
    'prohibited_if' => 'حقل :attribute محظور عندما تكون قيمة :other مساوية لـ :value.',
    'prohibited_if_accepted' => 'حقل :attribute محظور عند قبول :other.',
    'prohibited_if_declined' => 'حقل :attribute محظور عند رفض :other.',
    'prohibited_unless' => 'حقل :attribute محظور ما لم تكن قيمة :other ضمن :values.',
    'prohibits' => 'حقل :attribute يمنع وجود :other.',
    'regex' => 'صيغة :attribute غير صحيحة.',
    'required' => 'حقل :attribute مطلوب.',
    'required_array_keys' => 'يجب أن يحتوي :attribute على مدخلات لـ: :values.',
    'required_if' => 'حقل :attribute مطلوب عندما تكون قيمة :other هي :value.',
    'required_if_accepted' => 'حقل :attribute مطلوب عند قبول :other.',
    'required_if_declined' => 'حقل :attribute مطلوب عند رفض :other.',
    'required_unless' => 'حقل :attribute مطلوب ما لم تكن قيمة :other ضمن :values.',
    'required_with' => 'حقل :attribute مطلوب عند وجود :values.',
    'required_with_all' => 'حقل :attribute مطلوب عند وجود :values.',
    'required_without' => 'حقل :attribute مطلوب عند عدم وجود :values.',
    'required_without_all' => 'حقل :attribute مطلوب عند عدم وجود أي من :values.',
    'same' => 'يجب أن يتطابق :attribute مع :other.',
    'size' => [
        'array' => 'يجب أن يحتوي :attribute على :size عنصرًا.',
        'file' => 'يجب أن يكون حجم :attribute :size كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute :size.',
        'string' => 'يجب أن يكون طول :attribute :size حرفًا.',
    ],
    'starts_with' => 'يجب أن يبدأ :attribute بأحد القيم التالية: :values.',
    'string' => 'يجب أن يكون :attribute نصًا.',
    'timezone' => 'يجب أن يكون :attribute منطقة زمنية صحيحة.',
    'ulid' => 'يجب أن يكون :attribute معرّف ULID صحيحًا.',
    'unique' => 'قيمة :attribute مستخدمة من قبل.',
    'uploaded' => 'فشل رفع :attribute.',
    'uppercase' => 'يجب أن يكون :attribute بأحرف كبيرة.',
    'url' => 'يجب أن يكون :attribute رابطًا صحيحًا.',
    'uuid' => 'يجب أن يكون :attribute معرف UUID صحيحًا.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'name' => 'الاسم',
        'first_name' => 'الاسم الأول',
        'last_name' => 'اسم العائلة',
        'email' => 'البريد الإلكتروني',
        'password' => 'كلمة المرور',
        'password_confirmation' => 'تأكيد كلمة المرور',
        'current_password' => 'كلمة المرور الحالية',
        'locale' => 'اللغة',
    ],

];
