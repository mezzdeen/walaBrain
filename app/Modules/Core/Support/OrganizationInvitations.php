<?php

namespace App\Modules\Core\Support;

use App\Modules\Core\Enums\OrganizationRole;
use App\Modules\Core\Models\Admin;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\OrganizationInvitation;
use App\Modules\Core\Notifications\OrganizationOwnerInvitation;
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
     * Invite an address to own the organization, and mail them the link.
     *
     * The plaintext token is generated here, handed straight to the
     * notification, and then dropped: only its hash reaches the database.
     */
    public static function issue(Organization $organization, string $email, ?Admin $invitedBy = null): OrganizationInvitation
    {
        $plainToken = Str::random(self::TOKEN_LENGTH);

        $invitation = OrganizationInvitation::create([
            'organization_id' => $organization->getKey(),
            'email' => $email,
            'role' => OrganizationRole::Owner,
            'token' => self::hash($plainToken),
            'invited_by_admin_id' => $invitedBy?->getKey(),
            'expires_at' => now()->addDays(self::EXPIRES_AFTER_DAYS),
        ]);

        // The invitee has no user record to notify against yet.
        Notification::route('mail', $email)
            ->notify(new OrganizationOwnerInvitation($invitation, $plainToken));

        return $invitation;
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
