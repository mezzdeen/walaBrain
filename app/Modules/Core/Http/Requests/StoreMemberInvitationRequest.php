<?php

namespace App\Modules\Core\Http\Requests;

use App\Modules\Core\Enums\OrganizationRole;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Rules\NotADeletedAccount;
use App\Modules\Core\Support\OrganizationContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreMemberInvitationRequest extends FormRequest
{
    /**
     * Only someone the organization has granted the invite permission may issue
     * one, against the organization they are acting on.
     */
    public function authorize(): bool
    {
        return Gate::allows('inviteMembers', $this->organization());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        $organization = $this->organization();

        return [
            // A member is invited by address, the same as an owner is. The
            // address may or may not already belong to an account; either way it
            // may not already belong to this organization, nor already hold a
            // standing invitation to it, and never a deleted account whose
            // invitation could not be accepted.
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                new NotADeletedAccount,
                Rule::unique('organization_invitations', 'email')
                    ->where('organization_id', $organization->getKey()),
                function (string $attribute, mixed $value, \Closure $fail) use ($organization): void {
                    if ($organization->users()->where('email', $value)->exists()) {
                        $fail('core::invitations.errors.already_member')->translate();
                    }
                },
            ],

            // Ownership is granted from the platform, not handed out by an
            // organization to itself, so it is never an option here — only the
            // organization's own non-owner roles are.
            'role' => ['required', 'string', Rule::in($this->assignableRoles($organization))],
        ];
    }

    /**
     * Normalise the address before it is validated, matching how Fortify stores
     * one so an address that only differs in case resolves to the same account.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => Str::lower((string) $this->input('email'))]);
        }
    }

    /**
     * The organization the request is acting on.
     */
    public function organization(): Organization
    {
        return OrganizationContext::current();
    }

    /**
     * The names of the roles this organization may invite someone under.
     *
     * @return list<string>
     */
    private function assignableRoles(Organization $organization): array
    {
        return Role::query()
            ->where('guard_name', 'web')
            ->where('organization_id', $organization->getKey())
            ->where('name', '!=', OrganizationRole::Owner->value)
            ->pluck('name')
            ->all();
    }
}
