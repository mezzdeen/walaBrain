<?php

namespace Database\Seeders;

use App\Modules\Core\Database\Seeders\AdminSeeder;
use App\Modules\Core\Database\Seeders\DemoSeeder;
use App\Modules\Core\Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
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
