<?php

namespace App\Modules\Forms\Policies;

use App\Modules\Core\Models\Admin;
use App\Modules\Core\Models\User;
use App\Modules\Forms\Models\Form;

/**
 * Authorizes who may open and submit a form.
 *
 * Deliberately looser than reaching the board behind it. A form is the front
 * door of a process: a finance request is submitted by people who will never
 * be members of the finance space, and gating intake on space membership would
 * make every process unreachable by exactly the people it serves. Tenancy
 * scoping already confines a form to its own business line; within one, any
 * member may submit, and only a published form is offered at all.
 */
class FormPolicy
{
    /**
     * Determine whether the identity can open and submit the form.
     */
    public function view(User|Admin $identity, Form $form): bool
    {
        return $identity instanceof User && $form->isPublished();
    }
}
