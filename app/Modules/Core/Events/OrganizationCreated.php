<?php

namespace App\Modules\Core\Events;

use App\Modules\Core\Models\Organization;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * An organization has come into existence and been given its roles and its
 * default space.
 *
 * Exists so a module can furnish a new organization with whatever it needs
 * without Core having to know the module is installed. Core provisions what
 * Core owns and then announces it; Boards listens and adds the board a business
 * line starts with. Deleting the Boards directory removes that behaviour with
 * it, and nothing in Core changes.
 *
 * Dispatched rather than left to Eloquent's `created` model event, because
 * `DatabaseSeeder` mutes model events for every seeder it runs — a listener on
 * the model event would be skipped during seeding without a word.
 */
final readonly class OrganizationCreated
{
    use Dispatchable;

    public function __construct(public Organization $organization) {}
}
