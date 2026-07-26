<?php

namespace App\Modules\Core\Support;

use App\Modules\Core\Enums\OrganizationRole;
use App\Modules\Core\Models\Admin;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\OrganizationInvitation;
use App\Modules\Core\Models\User;
use App\Modules\Core\Notifications\OrganizationMemberInvitation;
use App\Modules\Core\Notifications\OrganizationOwnerInvitation;
use Illuminate\Notifications\Notification as NotificationInstance;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Issues and resolves the invitations sent to people without an account.
 */
final class OrganizationInvitations
{
    /**
     * How long an invitation link stays usable.
     */
    public const EXPIRES_AFTER_DAYS = 7;

    /**
     * How many characters of randomness the plaintext token carries.
     */
    private const TOKEN_LENGTH = 64;

    /**
     * Invite an address into the organization under a role, and mail the link.
     *
     * The plaintext token is generated here, handed straight to the
     * notification, and then dropped: only its hash reaches the database. The
     * inviter is either a platform admin provisioning a tenant's owner or a
     * company user staffing their own organization; the invitation records
     * whichever it was against the matching column.
     */
    public static function issue(
        Organization $organization,
        string $email,
        string $role = OrganizationRole::Owner->value,
        User|Admin|null $invitedBy = null,
    ): OrganizationInvitation {
        $plainToken = Str::random(self::TOKEN_LENGTH);

        $invitation = OrganizationInvitation::create([
            'organization_id' => $organization->getKey(),
            'email' => $email,
            'role' => $role,
            'token' => self::hash($plainToken),
            'invited_by_admin_id' => $invitedBy instanceof Admin ? $invitedBy->getKey() : null,
            'invited_by_user_id' => $invitedBy instanceof User ? $invitedBy->getKey() : null,
            'expires_at' => now()->addDays(self::EXPIRES_AFTER_DAYS),
        ]);

        // The invitee may have no user record to notify against yet, so the
        // mail is addressed to the raw address either way.
        Notification::route('mail', $email)
            ->notify(self::notificationFor($invitation, $plainToken));

        return $invitation;
    }

    /**
     * Mint a fresh token and window for an existing invitation, and mail it.
     *
     * The stored token is only a hash, so the plaintext the first mail carried
     * cannot be recovered to send again; a new one is generated and the old hash
     * replaced, which quietly retires the previous link.
     */
    public static function reissue(OrganizationInvitation $invitation): OrganizationInvitation
    {
        $plainToken = Str::random(self::TOKEN_LENGTH);

        $invitation->forceFill([
            'token' => self::hash($plainToken),
            'expires_at' => now()->addDays(self::EXPIRES_AFTER_DAYS),
        ])->save();

        Notification::route('mail', $invitation->email)
            ->notify(self::notificationFor($invitation, $plainToken));

        return $invitation;
    }

    /**
     * The mail an invitation is announced with, worded for the role it grants.
     */
    private static function notificationFor(OrganizationInvitation $invitation, string $plainToken): NotificationInstance
    {
        return $invitation->role === OrganizationRole::Owner->value
            ? new OrganizationOwnerInvitation($invitation, $plainToken)
            : new OrganizationMemberInvitation($invitation, $plainToken);
    }

    /**
     * Resolve a plaintext token to an invitation that can still be accepted.
     */
    public static function findPendingByToken(string $plainToken): ?OrganizationInvitation
    {
        return OrganizationInvitation::query()
            ->pending()
            ->where('token', self::hash($plainToken))
            ->first();
    }

    /**
     * Resolve a plaintext token to an invitation whatever its state.
     *
     * Used to tell "this link is expired" apart from "this link never existed",
     * which is the difference between a useful message and a dead end.
     */
    public static function findByToken(string $plainToken): ?OrganizationInvitation
    {
        return OrganizationInvitation::query()
            ->where('token', self::hash($plainToken))
            ->first();
    }

    /**
     * The stored form of a plaintext token.
     */
    public static function hash(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }
}
