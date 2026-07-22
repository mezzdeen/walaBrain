<?php

namespace App\Modules\Core\Http\Requests;

use App\Modules\Core\Models\Organization;
use App\Modules\Core\Rules\NotADeletedAccount;
use App\Modules\Core\Support\OrganizationContext;
use App\Modules\Core\Support\OrganizationRoles;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
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
     * @return array<string, ValidationRule|array<mixed>|string>
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
            //
            // Only a still-standing invitation blocks a fresh one: an expired or
            // already-accepted row is spent, so it must not wall the address off
            // from ever being invited again.
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                new NotADeletedAccount,
                Rule::unique('organization_invitations', 'email')
                    ->where('organization_id', $organization->getKey())
                    ->whereNull('accepted_at')
                    ->where(fn (Builder $query): Builder => $query->where('expires_at', '>', now())),
                function (string $attribute, mixed $value, \Closure $fail) use ($organization): void {
                    if ($organization->users()->where('email', $value)->exists()) {
                        $fail('core::invitations.errors.already_member')->translate();
                    }
                },
            ],

            // Ownership is granted from the platform, not handed out by an
            // organization to itself, so it is never an option here — only the
            // organization's own non-owner roles are.
            'role' => ['required', 'string', Rule::in(OrganizationRoles::assignableNames($organization))],
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
}
