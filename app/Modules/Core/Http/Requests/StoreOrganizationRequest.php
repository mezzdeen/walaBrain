<?php

namespace App\Modules\Core\Http\Requests;

use App\Modules\Core\Rules\NotADeletedAccount;
use App\Modules\Core\Support\Locale;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreOrganizationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],

            // An organization nobody can administer is not worth creating, so
            // the owner is named up front. The address may or may not belong to
            // an existing account; the controller decides what that means. What
            // it may not belong to is a deleted one — that address is spent, and
            // the invitation it would trigger could never be accepted.
            'owner_email' => ['required', 'string', 'lowercase', 'email', 'max:255', new NotADeletedAccount],

            // The language the organization's own people will write in. Optional
            // rather than required: it has a sensible default, and an admin
            // creating an organization for a team they are not on is often not
            // the person who knows the answer. It is the organization's to
            // change afterwards.
            'locale' => ['sometimes', 'nullable', 'string', Rule::in(Locale::SUPPORTED)],
        ];
    }

    /**
     * Normalise the owner address before it is validated.
     *
     * Fortify lowercases usernames on the way in, so an address that only
     * differs in case still has to resolve to the same account here.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('owner_email')) {
            $this->merge([
                'owner_email' => Str::lower((string) $this->input('owner_email')),
            ]);
        }
    }
}
