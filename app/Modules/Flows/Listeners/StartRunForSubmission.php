<?php

namespace App\Modules\Flows\Listeners;

use App\Modules\Flows\Models\Flow;
use App\Modules\Flows\Support\Runner;
use App\Modules\Forms\Events\FormSubmitted;

/**
 * Sets a submission in motion.
 *
 * Forms announces that a node exists; this is what Flows does about it. A form
 * without a published flow is still a working form — the node is created and
 * referenced, and nothing further happens, which is exactly what a form on its
 * own is documented to do.
 */
class StartRunForSubmission
{
    public function __construct(private readonly Runner $runner) {}

    public function handle(FormSubmitted $event): void
    {
        $flow = Flow::query()
            ->where('form_id', $event->form->getKey())
            ->whereNotNull('published_at')
            ->first();

        if ($flow instanceof Flow) {
            $this->runner->start($flow, $event->node);
        }
    }
}
