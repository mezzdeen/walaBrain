<?php

namespace App\Modules\Core\Database\Seeders;

use App\Modules\Core\Enums\SuperRole;
use App\Modules\Core\Models\Admin;
use App\Modules\Core\Support\PermissionTeam;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminSeeder extends Seeder
{
    /**
     * Seed a default super admin for the admin platform.
     */
    public function run(): void
    {
        // A soft-deleted admin still holds the email's unique index, so a
        // re-seed after one was removed has to bring it back rather than collide
        // with it. Restored first, then matched: once un-trashed it falls inside
        // the default scope `firstOrCreate` reads.
        $trashed = Admin::withTrashed()->firstWhere('email', config('core.admin.email'));

        if ($trashed?->trashed()) {
            $trashed->restore();
        }

        $admin = Admin::firstOrCreate(
            ['email' => config('core.admin.email')],
            [
                'name' => config('core.admin.name'),
                'password' => Hash::make($this->password()),
            ],
        );

        // Without a role this account is locked out of the platform it exists to
        // administer, since every route now requires a permission. Assignments
        // live on the sentinel team the super platform runs on.
        setPermissionsTeamId(PermissionTeam::SUPER);
        $admin->assignRole(SuperRole::SuperAdmin->value);
    }

    /**
     * The password the default admin is created with.
     *
     * Production must set one: a super admin created with a known fallback is a
     * back door into every organization, so the run fails loudly rather than
     * quietly opening one. Outside production a throwaway keeps local setup
     * frictionless.
     */
    private function password(): string
    {
        $password = config('core.admin.password');

        if (is_string($password) && $password !== '') {
            return $password;
        }

        if (app()->isProduction()) {
            throw new RuntimeException(
                'Set ADMIN_PASSWORD before seeding the super admin in production.',
            );
        }

        return 'password';
    }
}
