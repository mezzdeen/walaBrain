<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Roles UI Language Lines
    |--------------------------------------------------------------------------
    |
    | Interface strings for the role and permission management screens on both
    | platforms. Shared labels live in `common.php` instead.
    |
    */

    'title' => 'الأدوار',
    'description' => 'تحكّم في ما يُسمح به لكل دور على المنصة.',

    'new' => 'دور جديد',
    'new_breadcrumb' => 'جديد',
    'new_description' => 'عرّف دوراً والصلاحيات التي يمنحها.',
    'create_action' => 'إنشاء الدور',

    'edit_title' => 'تعديل الدور',
    'edit_breadcrumb' => 'تعديل',
    'edit_description' => 'حدّث الدور والصلاحيات التي يمنحها.',
    'edit_page_title' => 'تعديل :name',
    'save_changes' => 'حفظ التغييرات',

    'empty_title' => 'لا توجد أدوار بعد',
    'empty_description' => 'أنشئ أول دور للبدء.',

    'name' => 'الاسم',
    'name_placeholder' => 'الدعم',
    'actions' => 'الإجراءات',
    'assigned' => 'مُسند إلى',
    'assigned_count_one' => 'مشرف واحد',
    'assigned_count_other' => ':count مشرفين',

    'permissions' => 'الصلاحيات',
    'permissions_description' => 'اختر ما يُسمح لهذا الدور بفعله.',
    'permission_count_one' => 'صلاحية واحدة',
    'permission_count_other' => ':count صلاحيات',
    'select_all' => 'تحديد الكل',

    'protected' => 'محمي',
    'protected_description' => 'لا يمكن تعديل هذا الدور أو حذفه، حتى تبقى المنصة قابلة للإدارة دائماً.',

    'delete_title' => 'حذف الدور',
    'delete_description' => 'هل أنت متأكد من حذف :name؟ سيفقد المشرفون الذين يحملونه صلاحياته.',

    'created' => 'تم إنشاء الدور.',
    'updated' => 'تم تحديث الدور.',
    'deleted' => 'تم حذف الدور.',
    'member_updated' => 'تم تحديث أدوار العضو.',

    'organization_title' => 'الأدوار في :name',
    'organization_description' => 'الأدوار التي تملكها هذه المؤسسة، ومن يحملها.',
    'organization_breadcrumb' => 'الأدوار',
    'manage_roles' => 'إدارة الأدوار',
    'members_title' => 'الأعضاء',
    'members_description' => 'اختر الأدوار التي يحملها كل عضو في هذه المؤسسة.',
    'no_roles' => 'بلا أدوار',
    'owned_roles' => 'الأدوار المملوكة',

    'groups' => [
        'organizations' => 'المؤسسات',
        'admins' => 'المشرفون',
        'roles' => 'الأدوار',
        'organization' => 'المؤسسة',
        'members' => 'الأعضاء',
        'spaces' => 'المساحات',
        'diagnostics' => 'الفحوصات',
        'settings' => 'إعدادات المنصة',
    ],

    /*
    | Keyed by everything after the group, so a permission renders as its group
    | heading plus the ability below it rather than needing a line of its own.
    | Nested, not dotted: lookups walk the key one segment at a time, so a
    | literal 'members.manage' key could never be reached.
    */
    'abilities' => [
        'view' => 'عرض',
        'create' => 'إنشاء',
        'update' => 'تعديل',
        'delete' => 'حذف',
        'invite' => 'دعوة',
        'remove' => 'إزالة',
        'run' => 'تشغيل',
        'manage' => 'إدارة',
        'roles' => [
            'manage' => 'إدارة الأدوار',
        ],
    ],

    'errors' => [
        'permission_not_held' => 'لا يمكنك منح صلاحيات لا تملكها بنفسك.',
    ],

];
