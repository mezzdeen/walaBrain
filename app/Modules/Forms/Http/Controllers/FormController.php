<?php

namespace App\Modules\Forms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Boards\Models\Field;
use App\Modules\Boards\Models\Node;
use App\Modules\Core\Contracts\HaltsSubmission;
use App\Modules\Core\Models\User;
use App\Modules\Forms\Events\FormSubmitted;
use App\Modules\Forms\Models\Form;
use App\Modules\Forms\Support\FieldInput;
use App\Modules\Forms\Support\ReferenceNumbers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Opening a form and submitting it.
 *
 * A submission is one transaction, and the announcement to whatever automates
 * the rest is dispatched inside it: the node, its values, its reference, and
 * whatever a listener set in motion either all exist or none do. A listener
 * that cannot proceed halts the submission whole — a request that exists but
 * never started moving would otherwise sit invisible forever.
 */
class FormController extends Controller
{
    /**
     * The form, ready to fill in.
     */
    public function show(Form $form): Response
    {
        Gate::authorize('view', $form);

        return Inertia::render('forms/show', [
            'form' => [
                'hash_id' => $form->hash_id,
                'name' => $form->name,
                'board' => $form->board->name,
            ],
            'fields' => $form->board->fields->map(fn (Field $field): array => [
                'hash_id' => $field->hash_id,
                'name' => $field->name,
                'type' => $field->type->value,
                'options' => $field->options,
                'help' => $field->help,
                'is_required' => $field->is_required,
            ])->all(),
        ]);
    }

    /**
     * Create the node the submission describes.
     */
    public function store(Request $request, Form $form): RedirectResponse
    {
        Gate::authorize('view', $form);

        /** @var User $submitter */
        $submitter = $request->user();

        $fields = $form->board->fields;

        // Values arrive keyed by field code; rules are declared against those
        // same keys so an error lands on the input that caused it.
        $rules = [];
        foreach ($fields as $field) {
            $rules['values.'.$field->hash_id] = FieldInput::rulesFor($field);
        }

        $validated = $request->validate($rules, [], $fields->mapWithKeys(
            fn (Field $field): array => ['values.'.$field->hash_id => $field->name],
        )->all());

        try {
            $node = DB::transaction(function () use ($form, $fields, $validated, $submitter): Node {
                $node = new Node([
                    'board_id' => $form->board_id,
                    'title' => $form->name,
                    'reference' => ReferenceNumbers::issue($form),
                    'creator_id' => $submitter->getKey(),
                    'status' => 'in_review',
                ]);

                foreach ($fields as $field) {
                    $node->setValueFor($field, FieldInput::coerce(
                        $field,
                        $validated['values'][$field->hash_id] ?? null,
                    ));
                }

                $node->save();

                // The first entry on the node's timeline: who submitted, and what
                // they said. Values by field id, matching how the node stores them.
                $node->recordActivity('form.submitted', [
                    'form' => $form->name,
                    'form_version' => $form->version,
                    'reference' => $node->reference,
                ], $submitter);

                FormSubmitted::dispatch($form, $node, $submitter);

                return $node;
            });
        } catch (HaltsSubmission $halted) {
            // The listener could not proceed and the transaction has rolled
            // back: nothing was created, and the reason is the submitter's to
            // fix rather than ours.
            throw ValidationException::withMessages(['form' => $halted->submitterMessage()]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('forms::forms.submitted', ['reference' => (string) $node->reference]),
        ]);

        return to_route('nodes.show', $node->fresh());
    }
}
