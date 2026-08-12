<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Flows Language Lines
    |--------------------------------------------------------------------------
    */

    'decided' => 'Decision recorded.',
    'resubmitted' => 'Resubmitted for review.',
    'no_manager' => 'This request needs your manager\'s approval, and no manager is set for you. Ask your Business-Line Admin to set one.',
    'task_for' => 'Generated for :reference.',

    'decision' => [
        'title' => 'Your decision',
        'approve' => 'Approve',
        'reject' => 'Reject',
        'request_changes' => 'Request changes',
        'comment' => 'Comment',
        'comment_required' => 'Required when rejecting or requesting changes.',
        'submitted_by' => 'Submitted by :name on :date',
        'history' => 'Earlier decisions',
        'already_decided' => 'This approval has been decided.',
    ],

    'status' => [
        'in_review' => 'In review',
        'changes_requested' => 'Changes requested',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'pending' => 'Pending',
    ],

    'approvals' => [
        'title' => 'Waiting on your decision',
        'decide' => 'Decide',
        'requested' => 'Requested :date',
    ],

    'resubmit' => [
        'title' => 'Revise and resubmit',
        'description' => 'Update the values below and send the request back for review.',
        'submit' => 'Resubmit',
    ],

    'notifications' => [
        'approval_requested' => ':reference is waiting on your decision.',
        'decision_approved' => ':reference was approved.',
        'decision_rejected' => ':reference was rejected: :comment',
        'decision_changes_requested' => ':reference needs changes: :comment',
        'task_assigned' => 'New task: :title, due :due.',
    ],

    'mail' => [
        'approval_subject' => 'Decision needed: :reference',
        'approval_line' => 'Request :reference is waiting on your decision.',
        'approval_action' => 'Decide',
        'decision_subject' => 'Update on :reference',
        'decision_approved' => 'Your request :reference was approved.',
        'decision_rejected' => 'Your request :reference was rejected.',
        'decision_changes_requested' => 'Your request :reference needs changes before it can proceed.',
        'decision_comment' => 'Comment: :comment',
        'decision_action' => 'View request',
        'task_subject' => 'New task: :title',
        'task_line' => 'You have been assigned ":title", due :due.',
        'task_action' => 'Open My Work',
    ],

];
