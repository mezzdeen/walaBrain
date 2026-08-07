<?php

namespace App\Modules\Core\Support;

use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\Space;

/**
 * Gives an organization the space it starts with.
 *
 * Called explicitly from the places that create an organization rather than
 * from a model observer, for the same reason {@see OrganizationRoles} is:
 * `DatabaseSeeder` mutes model events for itself and every seeder it calls, so
 * an observer would be skipped during seeding without a word.
 */
final class Spaces
{
    /**
     * Create the organization's default space, if it does not have one yet.
     *
     * Safe to call more than once. The name is a translation because nobody has
     * typed one yet — an organization that has just come into existence has no
     * author to write it — and it is theirs to rename afterwards like any other.
     */
    public static function provisionDefault(Organization $organization): Space
    {
        return OrganizationContext::for($organization, function (): Space {
            $existing = Space::query()->where('is_default', true)->first();

            if ($existing instanceof Space) {
                return $existing;
            }

            return Space::create([
                'name' => __('core.spaces.default_name'),
                'position' => 0,
                'is_default' => true,
            ]);
        });
    }

    /**
     * Where the next space should sit in the organization's running order.
     *
     * Derived from the highest position in use rather than from how many spaces
     * exist, so deleting one from the middle does not hand the next new space a
     * position something else already holds.
     */
    public static function nextPosition(Organization $organization): int
    {
        return OrganizationContext::for(
            $organization,
            fn (): int => ((int) Space::query()->max('position')) + 1,
        );
    }
}
