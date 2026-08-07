<?php

namespace App\Modules\Boards\Support;

use App\Modules\Boards\Models\Board;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\Space;
use App\Modules\Core\Support\OrganizationContext;

/**
 * Gives an organization the board it starts with.
 *
 * A business line that opens on an empty screen has nothing for anyone to do,
 * and a task somebody writes for themselves belongs to no process and so has no
 * board of its own to go on. One default board answers both.
 */
final class Boards
{
    /**
     * Create the organization's default board, if it does not have one yet.
     *
     * Safe to call more than once. Placed in the organization's default space,
     * which every member reaches without being added to it, so the board is
     * reachable by everyone from the moment it exists.
     */
    public static function provisionDefault(Organization $organization): ?Board
    {
        return OrganizationContext::for($organization, function () use ($organization): ?Board {
            $existing = Board::query()->where('is_default', true)->first();

            if ($existing instanceof Board) {
                return $existing;
            }

            $space = Space::query()->where('is_default', true)->first();

            if (! $space instanceof Space) {
                // Nothing to hang it on. Core provisions the default space with
                // the organization, so this only happens to an organization
                // assembled by hand, and inventing a space to fix it would hide
                // that rather than surface it.
                return null;
            }

            return Board::create([
                'space_id' => $space->getKey(),
                'name' => __('boards::boards.default_name', [], $organization->locale),
                'position' => 0,
                'is_default' => true,
            ]);
        });
    }
}
