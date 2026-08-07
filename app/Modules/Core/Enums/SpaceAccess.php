<?php

namespace App\Modules\Core\Enums;

/**
 * What someone added to a space may do there.
 *
 * Deliberately not a permission. Roles say what a person is allowed to do
 * anywhere in their business line; this says where they may do it. Keeping the
 * two apart means adding someone to a space never quietly grants them an
 * ability, and granting an ability never quietly opens a space.
 */
enum SpaceAccess: string
{
    /** Open the space's boards and read what is on them. */
    case View = 'view';

    /** Everything View allows, plus creating and changing what is on them. */
    case Edit = 'edit';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Whether the access allows changing what is in the space.
     */
    public function allowsEditing(): bool
    {
        return $this === self::Edit;
    }
}
