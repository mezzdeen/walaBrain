<?php

namespace App\Modules\Core\Database\Seeders;

use App\Modules\Core\Enums\SuperRole;
use App\Modules\Core\Models\Admin;
use App\Modules\Core\Support\PermissionTeam;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Seed a default super admin for the admin platform.
     */
    public function run(): void
    {
        $admin = Admin::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@syaaq.com')],
            [
                'name' => env('ADMIN_NAME', 'Admin'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
            ],
        );

        // Without a role this account is locked out of the platform it exists to
        // administer, since every route now requires a permission. Assignments
        // live on the sentinel team the super platform runs on.
        setPermissionsTeamId(PermissionTeam::SUPER);
        $admin->assignRole(SuperRole::SuperAdmin->value);
    }
}
