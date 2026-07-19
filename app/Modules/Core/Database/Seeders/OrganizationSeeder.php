<?php

namespace App\Modules\Core\Database\Seeders;

use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;
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
                if ($users->isNotEmpty()) {
                    $organization->users()->attach($users->random(min(2, $users->count())));
                }
            });
    }
}
