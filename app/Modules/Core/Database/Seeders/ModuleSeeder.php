<?php

namespace App\Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    /**
     * Seed everything the module owns.
     *
     * The entry point the application's seeder looks for, so that adding or
     * removing a module never means editing `DatabaseSeeder`.
     */
    public function run(): void
    {
        // First: the admin is given a role from this catalogue, and every
        // organization's roles are provisioned against the permissions in it.
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(AdminSeeder::class);
        $this->call(DemoSeeder::class);
    }
}
