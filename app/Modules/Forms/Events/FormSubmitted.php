<?php

namespace App\Modules\Forms\Events;

use App\Modules\Boards\Models\Node;
use App\Modules\Core\Models\User;
use App\Modules\Forms\Models\Form;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A form has been submitted and its node exists, referenced and populated.
 *
 * What connects intake to automation without coupling them: Forms announces,
 * and whether a flow starts is the business of whatever listens. Deleting the
 * Flows directory leaves submissions creating nodes exactly as before —
 * they just stop setting anything in motion.
 */
final readonly class FormSubmitted
{
    use Dispatchable;

    public function __construct(
        public Form $form,
        public Node $node,
        public User $submitter,
    ) {}
}
