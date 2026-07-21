<?php

use App\Modules\Core\Enums\OrganizationRole;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Support\OrganizationRoles;
use Illuminate\Database\Migrations\Migration;

/**
 * Give organizations that already existed their default roles.
 *
 * Roles are provisioned when an organization is created, which does nothing for
 * the ones created before this feature landed. Without this they would have no
 * roles at all, so every member would hold no permissions and no one — not even
 * the intended owner — could reach the screen that creates the first role.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Global scopes are dropped throughout: this migration reads models
        // through their classes as they are *today*, and a scope added after it
        // was written filters on a column that does not exist yet at this point
        // in the migration run. Soft deletes are the case in hand.
        Organization::query()->withoutGlobalScopes()->each(function (Organization $organization): void {
            OrganizationRoles::provision($organization);

            // The longest-standing member administers the organization, since
            // membership carried no notion of ownership before now. Nothing to
            // undo if an organization has no members yet.
            $owner = $organization->users()
                ->withoutGlobalScopes()
                ->oldest('organization_user.created_at')
                ->first();

            if ($owner === null) {
                return;
            }

            OrganizationRoles::within(
                $organization,
                fn () => $owner->assignRole(OrganizationRole::Owner->value),
            );
        });
    }

    public function down(): void
    {
        // Provisioned roles are indistinguishable from ones an organization
        // went on to edit, so removing them here would destroy real work.
    }
};
