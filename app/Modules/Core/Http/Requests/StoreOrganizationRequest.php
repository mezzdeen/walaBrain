<?php

namespace App\Modules\Core\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

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
            // an existing account; the controller decides what that means.
            'owner_email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
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
