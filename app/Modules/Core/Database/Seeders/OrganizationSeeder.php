<?php

namespace App\Modules\Core\Database\Seeders;

use App\Modules\Core\Enums\OrganizationRole;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\OrganizationRoles;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    /**
     * Seed a few organizations and link them to the existing users.
     */
    public function run(): void
    {
        $users = User::all();

        Organization::factory()
            ->count(3)
            ->create()
            ->each(function (Organization $organization) use ($users): void {
                OrganizationRoles::provision($organization);

                if ($users->isNotEmpty()) {
                    $members = $users->random(min(2, $users->count()));
                    $organization->users()->attach($members);

                    // The first member administers the organization.
                    OrganizationRoles::within($organization, function () use ($members): void {
                        collect($members)->first()?->assignRole(OrganizationRole::Owner->value);
                    });
                }
            });
    }
}
