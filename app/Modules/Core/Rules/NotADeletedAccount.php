<?php

namespace App\Modules\Core\Rules;

use App\Modules\Core\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Refuses an address that belongs to an account someone has deleted.
 *
 * A deleted account is invisible to an ordinary query but its row still holds
 * the address against the unique index on `users.email`. Nothing can be built
 * on top of that address: naming it as an owner issues an invitation whose
 * registration form cannot be submitted, because the constraint rejects the
 * insert that the form exists to make.
 *
 * Live accounts pass. Holding the address is what an existing owner is for.
 */
class NotADeletedAccount implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $isDeleted = User::onlyTrashed()->where('email', $value)->exists();

        if ($isDeleted) {
            $fail('core::validation.deleted_account')->translate();
        }
    }
}
