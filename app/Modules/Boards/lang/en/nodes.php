<?php

return [

    'timeline' => 'Timeline',
    'by_system' => 'System',

    /*
    | Timeline entry types, nested to mirror the dots in the type strings:
    | `form.submitted` reads as events.form.submitted.
    */
    'events' => [
        'form' => [
            'submitted' => 'Submitted',
            'resubmitted' => 'Resubmitted with changes',
        ],
        'run' => [
            'started' => 'Process started',
            'completed' => 'Approved — process complete',
            'rejected' => 'Process ended: rejected',
        ],
        'approval' => [
            'requested' => 'Approval requested',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'changes_requested' => 'Changes requested',
        ],
        'task' => [
            'created' => 'Task created',
            'generated' => 'Task generated',
            'completed' => 'Task completed',
        ],
    ],

];
