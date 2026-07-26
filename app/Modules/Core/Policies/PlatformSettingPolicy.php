<?php

namespace App\Modules\Core\Policies;

use App\Modules\Core\Enums\SuperPermission;
use App\Modules\Core\Models\Admin;
use App\Modules\Core\Models\User;

/**
 * Authorizes the platform's own settings.
 *
 * Unlike {@see OrganizationPolicy}, there is no company side to branch to: how
 * the platform behaves is not something a tenant has a say in, so a `User` is
 * refused outright rather than checked against an organization permission. The
 * `instanceof` test is what enforces that, for the same reason it is written
 * that way there — the answer must not depend on which guard happens to be
 * default when the gate runs.
 */
class PlatformSettingPolicy
{
    /**
     * Determine whether the identity can read and change the platform settings.
     *
     * One ability, not a view/update pair: the screen is the form, so anyone who
     * can open it can submit it. See {@see SuperPermission::ManagePlatformSettings}.
     */
    public function update(User|Admin $identity): bool
    {
        return $identity instanceof Admin
            && $identity->can(SuperPermission::ManagePlatformSettings->value);
    }
}
