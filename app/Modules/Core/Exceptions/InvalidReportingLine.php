<?php

namespace App\Modules\Core\Exceptions;

use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;
use RuntimeException;

/**
 * Thrown when a manager could not be set because the reporting line it would
 * produce is not one the organization could actually have.
 */
final class InvalidReportingLine extends RuntimeException
{
    public static function notAMember(User $manager, Organization $organization): self
    {
        return new self(sprintf(
            'User [%d] cannot manage anyone in organization [%d] because they are not a member of it. A reporting line never crosses a business line.',
            $manager->getKey(),
            $organization->getKey(),
        ));
    }

    public static function selfReferential(User $user): self
    {
        return new self(sprintf('User [%d] cannot be their own manager.', $user->getKey()));
    }

    public static function circular(User $member, User $manager): self
    {
        return new self(sprintf(
            'Making user [%d] the manager of user [%d] would close a loop in the reporting line, leaving it with no top.',
            $manager->getKey(),
            $member->getKey(),
        ));
    }
}
