<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Enums\OrganizationRole;
use App\Modules\Core\Http\Requests\StoreMemberInvitationRequest;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\OrganizationInvitation;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Support\OrganizationContext;
use App\Modules\Core\Support\OrganizationInvitations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Where an organization invites people into itself.
 *
 * Distinct from {@see InvitationController}, which is the other end of the
 * same story: a visitor accepting an invitation by token, signed in or not.
 *
 * Every invitation issued here stays pending until it is accepted, existing
 * accounts included, so the organization never appears to someone who has not
 * taken it up. The role is chosen from the organization's own roles, ownership
 * excepted — that is the platform's to grant.
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

        return Inertia::render('invitations/index', [
            'roles' => $this->assignableRoles($organization),
            'invitations' => $this->pendingInvitations($organization),
        ]);
    }

    /**
     * Issue an invitation to the current organization under the chosen role.
     */
    public function store(StoreMemberInvitationRequest $request): RedirectResponse
    {
        OrganizationInvitations::issue(
            $request->organization(),
            $request->validated('email'),
            $request->validated('role'),
            $request->user(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('core::invitations.manage.sent')]);

        return to_route('invitations.index');
    }

    /**
     * Withdraw a pending invitation.
     */
    public function destroy(OrganizationInvitation $invitation): RedirectResponse
    {
        $this->authorizeForOrganization($invitation);

        $invitation->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('core::invitations.manage.cancelled')]);

        return to_route('invitations.index');
    }

    /**
     * Reissue a pending invitation with a fresh link and window.
     *
     * A new token is minted rather than the old one resent: only its hash is
     * stored, so the plaintext the first mail carried is gone and cannot be
     * mailed again.
     */
    public function resend(OrganizationInvitation $invitation): RedirectResponse
    {
        $this->authorizeForOrganization($invitation);

        OrganizationInvitations::reissue($invitation);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('core::invitations.manage.resent')]);

        return to_route('invitations.index');
    }

    /**
     * Confirm the invitation belongs to the organization being acted on before
     * anything is done to it, then that the actor may invite into it.
     *
     * The organization check comes first so an invitation from another tenant is
     * a 404 — the same answer an invitation that never existed gets — rather
     * than a 403 that would confirm it is real.
     */
    private function authorizeForOrganization(OrganizationInvitation $invitation): void
    {
        $organization = OrganizationContext::current();

        abort_unless($invitation->organization_id === $organization->getKey(), 404);

        Gate::authorize('inviteMembers', $organization);
    }

    /**
     * The roles the organization may invite someone under: its own, ownership
     * excepted.
     *
     * @return list<array{value: string, label: string}>
     */
    private function assignableRoles(Organization $organization): array
    {
        return Role::query()
            ->where('guard_name', 'web')
            ->where('organization_id', $organization->getKey())
            ->where('name', '!=', OrganizationRole::Owner->value)
            ->orderBy('name')
            ->pluck('name')
            ->map(fn (string $name): array => ['value' => $name, 'label' => $name])
            ->all();
    }

    /**
     * The organization's still-open invitations, newest first.
     *
     * @return list<array{hash_id: string, email: string, role: string, expires_at: string}>
     */
    private function pendingInvitations(Organization $organization): array
    {
        return $organization->invitations()
            ->pending()
            ->latest()
            ->get(['id', 'email', 'role', 'expires_at'])
            ->map(fn (OrganizationInvitation $invitation): array => [
                'hash_id' => $invitation->hash_id,
                'email' => $invitation->email,
                'role' => $invitation->role,
                'expires_at' => $invitation->expires_at->toIso8601String(),
            ])
            ->all();
    }
}
