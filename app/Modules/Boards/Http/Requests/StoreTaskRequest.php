<?php

namespace App\Modules\Boards\Http\Requests;

use App\Modules\Core\Enums\OrganizationPermission;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\HashId;
use App\Modules\Core\Support\OrganizationContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Writing a task by hand, for yourself or for somebody else.
 *
 * Who may be given work is the whole of the interesting part here. Assigning to
 * yourself needs nothing at all. Assigning to somebody else is allowed to their
 * manager, because that is what a reporting line is for, and to anyone holding
 * the capability for routing work generally. Everyone else is refused, so a
 * task cannot be pushed onto a colleague by anybody who happens to know their
 * code.
 */
class StoreTaskRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'due_date' => ['nullable', 'date'],

            // The person's public code, not their key: this arrives from a form
            // and a sequential id in a payload discloses the same thing one in
            // a URL would.
            'assignee' => ['nullable', 'string'],
        ];
    }

    /**
     * Resolve and authorize the assignee once the rest has validated.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $assignee = $this->resolveAssignee();

                if ($assignee === null) {
                    $validator->errors()->add('assignee', __('boards::tasks.assignee_unknown'));

                    return;
                }

                if (! $this->mayAssignTo($assignee)) {
                    $validator->errors()->add('assignee', __('boards::tasks.assignee_not_allowed'));
                }
            },
        ];
    }

    /**
     * The person the task is for: whoever was named, or the requester.
     */
    public function resolveAssignee(): ?User
    {
        $code = $this->input('assignee');

        /** @var User $actor */
        $actor = $this->user();

        if (! is_string($code) || $code === '') {
            return $actor;
        }

        $key = HashId::decode($code);

        if ($key === null) {
            return null;
        }

        $organization = OrganizationContext::current();

        if ($organization === null) {
            return null;
        }

        // Looked up through the organization's own members, so a code naming a
        // real account outside this business line resolves to nobody.
        return $organization->users()->whereKey($key)->first();
    }

    /**
     * Whether the requester may hand work to this person.
     */
    private function mayAssignTo(User $assignee): bool
    {
        /** @var User $actor */
        $actor = $this->user();

        $organization = OrganizationContext::current();

        return $assignee->is($actor)
            || ($organization !== null && $actor->managesInOrganization($assignee, $organization))
            || $actor->can(OrganizationPermission::AssignTasks->value);
    }
}
