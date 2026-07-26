<?php

namespace App\Modules\Core\Support;

use Illuminate\Http\Request;

/**
 * Which of the module's two platforms is serving the request.
 *
 * One answer in one place: the props middleware, the organization context and
 * anything else that has to tell them apart all read it here, so moving the
 * admin platform is an edit to this file rather than to every caller.
 */
class Platform
{
    /**
     * The path the admin platform is mounted under.
     */
    public const ADMIN_PREFIX = 'super';

    /**
     * Whether the request is being served by the admin platform.
     *
     * Decided by path rather than by which guard is authenticated, because a
     * browser can hold a session on both platforms at once and each must only
     * ever see its own identity's data.
     */
    public static function isAdmin(Request $request): bool
    {
        return $request->is(self::ADMIN_PREFIX, self::ADMIN_PREFIX.'/*');
    }
}
