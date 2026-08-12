<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Flows Language Lines
    |--------------------------------------------------------------------------
    */

    'decided' => 'سُجل القرار.',
    'resubmitted' => 'أُعيد الإرسال للمراجعة.',
    'no_manager' => 'يحتاج هذا الطلب موافقة مديرك، ولم يُحدَّد لك مدير بعد. اطلب من مسؤول وحدة العمل تحديده.',
    'task_for' => 'أُنشئت للطلب :reference.',

    'decision' => [
        'title' => 'قرارك',
        'approve' => 'موافقة',
        'reject' => 'رفض',
        'request_changes' => 'طلب تعديلات',
        'comment' => 'تعليق',
        'comment_required' => 'مطلوب عند الرفض أو طلب التعديلات.',
        'submitted_by' => 'قدمه :name في :date',
        'history' => 'قرارات سابقة',
        'already_decided' => 'سبق البت في هذه الموافقة.',
    ],

    'status' => [
        'in_review' => 'قيد المراجعة',
        'changes_requested' => 'مطلوب تعديلات',
        'approved' => 'موافَق عليه',
        'rejected' => 'مرفوض',
        'pending' => 'معلّق',
    ],

    'approvals' => [
        'title' => 'بانتظار قرارك',
        'decide' => 'البت',
        'requested' => 'طُلب في :date',
    ],

    'resubmit' => [
        'title' => 'التعديل وإعادة الإرسال',
        'description' => 'حدّث القيم أدناه وأعد إرسال الطلب للمراجعة.',
        'submit' => 'إعادة الإرسال',
    ],

    'notifications' => [
        'approval_requested' => 'الطلب :reference بانتظار قرارك.',
        'decision_approved' => 'تمت الموافقة على :reference.',
        'decision_rejected' => 'رُفض الطلب :reference: :comment',
        'decision_changes_requested' => 'الطلب :reference يحتاج تعديلات: :comment',
        'task_assigned' => 'مهمة جديدة: :title، تستحق :due.',
    ],

    'mail' => [
        'approval_subject' => 'قرار مطلوب: :reference',
        'approval_line' => 'الطلب :reference بانتظار قرارك.',
        'approval_action' => 'البت في الطلب',
        'decision_subject' => 'تحديث بشأن :reference',
        'decision_approved' => 'تمت الموافقة على طلبك :reference.',
        'decision_rejected' => 'رُفض طلبك :reference.',
        'decision_changes_requested' => 'طلبك :reference يحتاج تعديلات قبل المتابعة.',
        'decision_comment' => 'التعليق: :comment',
        'decision_action' => 'عرض الطلب',
        'task_subject' => 'مهمة جديدة: :title',
        'task_line' => 'أُسندت إليك ":title"، تستحق :due.',
        'task_action' => 'فتح أعمالي',
    ],

];
