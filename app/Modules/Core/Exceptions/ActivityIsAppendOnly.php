<?php

namespace App\Modules\Core\Exceptions;

use RuntimeException;

/**
 * Thrown when something tries to change or remove an activity entry.
 *
 * The database refuses these outright; this exists so the attempt fails in PHP,
 * with an explanation, rather than surfacing as a driver error from a trigger.
 */
final class ActivityIsAppendOnly extends RuntimeException
{
    public static function updated(): self
    {
        return new self(
            'An activity entry cannot be updated. The timeline is append-only: record a new entry describing the correction instead.',
        );
    }

    public static function deleted(): self
    {
        return new self(
            'An activity entry cannot be deleted. The timeline is append-only, and history that can be removed is not history.',
        );
    }
}
