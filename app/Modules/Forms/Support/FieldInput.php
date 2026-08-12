<?php

namespace App\Modules\Forms\Support;

use App\Modules\Boards\Enums\FieldType;
use App\Modules\Boards\Models\Field;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\HashId;
use App\Modules\Core\Support\OrganizationContext;
use Illuminate\Validation\Rule;

/**
 * How a submitted value is checked and stored, per field type.
 *
 * The one place the storage contract in {@see FieldType::storedAs()} is
 * enforced on the way in: a money value leaves here as a number, a date as
 * ISO 8601, a person as a member's id. A value written any other way breaks
 * sorting and filtering for every node on the board, not just its own.
 */
final class FieldInput
{
    /**
     * The validation rules for one field, keyed for the values payload.
     *
     * @return list<mixed>
     */
    public static function rulesFor(Field $field): array
    {
        $rules = [$field->is_required ? 'required' : 'nullable'];

        return array_merge($rules, match ($field->type) {
            FieldType::Text => ['string', 'max:255'],
            FieldType::LongText => ['string', 'max:10000'],
            FieldType::Number, FieldType::Money => ['numeric'],
            FieldType::Date => ['date'],
            FieldType::SingleSelect, FieldType::Status => [Rule::in($field->options ?? [])],
            FieldType::MultiSelect => ['array', Rule::in($field->options ?? [])],
            // Arrives as a member's public code; membership is checked in
            // coerce(), where the organization is known.
            FieldType::Person => ['string'],
            // Deferred with storage itself; a schema may declare one, but a
            // submission cannot fill it yet.
            FieldType::File => ['prohibited'],
        });
    }

    /**
     * Turn a validated input into the value the node stores, or null when it
     * cannot be resolved — a person code that names nobody in this
     * organization, for one.
     */
    public static function coerce(Field $field, mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($field->type) {
            FieldType::Number, FieldType::Money => (float) $value,
            FieldType::Date => date('Y-m-d', (int) strtotime((string) $value)),
            FieldType::Person => self::memberId((string) $value),
            FieldType::MultiSelect => array_values((array) $value),
            default => (string) $value,
        };
    }

    /**
     * Resolve a person code to a member of the active organization.
     *
     * Through the organization's own members, so a code naming a real account
     * in another business line resolves to nobody — the same rule assignment
     * follows everywhere else.
     */
    private static function memberId(string $code): ?int
    {
        $key = HashId::decode($code);
        $organization = OrganizationContext::current();

        if ($key === null || $organization === null) {
            return null;
        }

        /** @var User|null $member */
        $member = $organization->users()->whereKey($key)->first();

        return $member?->getKey();
    }
}
