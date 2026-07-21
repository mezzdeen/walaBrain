<?php

namespace App\Modules\Core\Http\Requests;

use App\Modules\Core\Concerns\PasswordValidationRules;
use App\Modules\Core\Concerns\ProfileValidationRules;
use App\Modules\Core\Models\User;
use App\Modules\Core\Rules\NotDisposableEmail;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->profileRules(),
            'email' => $this->registrationEmailRules(),
            'password' => $this->passwordRules(),
        ];
    }

    /**
     * The address rules, stricter than the ones a profile update uses.
     *
     * The extra checks live here rather than in `ProfileValidationRules` on
     * purpose. This is the one moment the address is a claim rather than a
     * fact: everyone else on the platform arrived by clicking a link that was
     * mailed to them, which already established that someone reads it. Putting
     * these in the shared concern would also hold existing users to a rule that
     * did not exist when they signed up, the first time they touched their
     * profile.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    private function registrationEmailRules(): array
    {
        return [
            'required',
            'string',

            // `dns` asks whether the domain accepts mail at all, which is what
            // separates a well formed address from a reachable one. Switched
            // off in tests, where a network call has no business being.
            config('core.verify_email_dns') ? 'email:rfc,dns' : 'email:rfc',

            'max:255',
            new NotDisposableEmail,
            Rule::unique(User::class),
        ];
    }
}
