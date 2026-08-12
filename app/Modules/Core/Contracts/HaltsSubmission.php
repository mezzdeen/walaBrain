<?php

namespace App\Modules\Core\Contracts;

use Throwable;

/**
 * An exception a submission listener may throw to stop the submission itself.
 *
 * Intake dispatches its event inside the submission's transaction, so a
 * listener that cannot proceed — an approval step that resolves to nobody, for
 * one — rolls the whole submission back rather than leaving a request that
 * exists but never started moving. Implementing this says the failure is the
 * submitter's to fix, and gives intake something to tell them; any other
 * exception stays a plain error, because it is ours to fix, not theirs.
 *
 * Lives in Core so the module that announces the event and the module that
 * halts it never have to name each other.
 */
interface HaltsSubmission extends Throwable
{
    /**
     * What the submitter should be told, in their interface language.
     */
    public function submitterMessage(): string;
}
