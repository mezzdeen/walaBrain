<?php

namespace App\Modules\Flows\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Boards\Models\Node;
use App\Modules\Core\Models\User;
use App\Modules\Flows\Enums\RunStatus;
use App\Modules\Flows\Models\Run;
use App\Modules\Flows\Support\Runner;
use App\Modules\Forms\Support\FieldInput;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * The submitter's half of the request-changes loop: revise the values and
 * send the same request back, under the same reference, to the same step.
 */
class ResubmissionController extends Controller
{
    public function __construct(private readonly Runner $runner) {}

    /**
     * Revise and resubmit the node.
     */
    public function store(Request $request, Node $node): RedirectResponse
    {
        Gate::authorize('resubmit', $node);

        $run = Run::query()
            ->where('node_id', $node->getKey())
            ->where('status', RunStatus::Waiting->value)
            ->firstOrFail();

        /** @var User $submitter */
        $submitter = $request->user();

        $fields = $node->board->fields;

        $rules = [];
        foreach ($fields as $field) {
            $rules['values.'.$field->hash_id] = FieldInput::rulesFor($field);
        }

        $validated = $request->validate($rules);

        DB::transaction(function () use ($node, $fields, $validated, $run, $submitter): void {
            foreach ($fields as $field) {
                $node->setValueFor($field, FieldInput::coerce(
                    $field,
                    $validated['values'][$field->hash_id] ?? null,
                ));
            }

            $node->save();

            $this->runner->resubmit($run, $submitter);
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('flows::flows.resubmitted'),
        ]);

        return to_route('nodes.show', $node);
    }
}
