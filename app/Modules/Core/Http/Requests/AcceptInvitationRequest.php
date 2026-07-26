<?php

namespace App\Modules\Core\Http\Requests;

use App\Modules\Core\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class AcceptInvitationRequest extends FormRequest
{
    use ProfileValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * The address is not validated here: it comes from the invitation rather
     * than from the form, so only the name and password are the invitee's to
     * supply.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => $this->nameRules(),
            'last_name' => $this->nameRules(),
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }
}
