<?php

return [

    'timeline' => 'السجل الزمني',
    'by_system' => 'النظام',

    /*
    | أنواع بنود السجل، متداخلة لتطابق النقاط في أسماء الأنواع.
    */
    'events' => [
        'form' => [
            'submitted' => 'أُرسل الطلب',
            'resubmitted' => 'أُعيد الإرسال بعد التعديل',
        ],
        'run' => [
            'started' => 'بدأت العملية',
            'completed' => 'تمت الموافقة — اكتملت العملية',
            'rejected' => 'انتهت العملية: مرفوض',
        ],
        'approval' => [
            'requested' => 'طُلبت الموافقة',
            'approved' => 'تمت الموافقة',
            'rejected' => 'رُفض',
            'changes_requested' => 'طُلبت تعديلات',
        ],
        'task' => [
            'created' => 'أُنشئت مهمة',
            'generated' => 'وُلدت مهمة',
            'completed' => 'أُنجزت مهمة',
        ],
    ],

];
