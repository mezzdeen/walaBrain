<?php

namespace App\Modules\Core\Actions\Fortify;

use App\Modules\Core\Concerns\PasswordValidationRules;
use App\Modules\Core\Concerns\ProfileValidationRules;
use App\Modules\Core\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        // Lower-cased before validation for the same reason the sign-up and
        // profile requests do it: the `lowercase` rule in `profileRules()`
        // enforces it, and Fortify matches the address in lower case at
        // sign-in, so an account created under a mixed-case spelling could
        // never be reached.
        if (isset($input['email'])) {
            $input['email'] = Str::lower($input['email']);
        }

        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        return User::create([
            'first_name' => $input['first_name'],
            'last_name' => $input['last_name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);
    }
}
