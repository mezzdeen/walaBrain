<?php

namespace App\Modules\Core\Http\Requests\Settings;

use App\Modules\Core\Concerns\PasswordValidationRules;
use App\Modules\Core\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class ProfileUpdateRequest extends FormRequest
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = $this->profileRules($this->user()->id);

        // The address alone is held to the account password: it is what a reset
        // link and every security notice are sent to, so changing it is how a
        // borrowed session is turned into a takeover. A name change is not
        // sensitive that way, so the field is asked for only when the address
        // actually differs. Compared after `prepareForValidation()` has
        // lower-cased the input, so a change of case is not treated as a change.
        if ($this->input('email') !== $this->user()->email) {
            $rules['current_password'] = $this->currentPasswordRules();
        }

        return $rules;
    }

    /**
     * Normalise the address before it is validated, so changing it to a
     * mixed-case spelling does not lock the account out at the next sign-in.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => Str::lower((string) $this->input('email'))]);
        }
    }
}
