<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Support\OrganizationContext;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Where an organization invites people into itself.
 *
 * Distinct from {@see InvitationController}, which is the other end of the
 * same story: a signed-out visitor accepting an invitation by token.
 *
 * Currently the screen only exists — issuing a member invitation is not wired
 * up yet, and is more than it looks. `OrganizationInvitations::issue()` takes
 * an `?Admin` inviter and hardcodes the owner role, `invited_by_admin_id` is a
 * foreign key to `admins` alone, and the accept flow assigns ownership without
 * ever reading the stored role. Any of that has to change before a member can
 * be invited, so it is left as its own piece of work.
 */
class MemberInvitationController extends Controller
{
    /**
     * Show the invitation screen for the current organization.
     */
    public function index(): Response
    {
        $organization = OrganizationContext::current();

        Gate::authorize('inviteMembers', $organization);

        return Inertia::render('invitations/index');
    }
}
