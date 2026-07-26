<?php

namespace App\Modules\Core\Models;

use App\Modules\Core\Concerns\HasHashId;
use App\Modules\Core\Database\Factories\OrganizationInvitationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A standing offer for someone to join an organization, held until accepted.
 *
 * Issued two ways. The platform provisions an organization for an address with
 * no account, and invites it to own the new tenant. A company then invites
 * people into that tenant — existing accounts among them — under a role it
 * chooses, and the offer stays pending for everyone until they accept, so the
 * organization never appears to someone who has not taken it up.
 *
 * The role is stored as a plain name rather than an enum: an organization can
 * invite under any role it owns, including ones it defined itself, which no
 * enum could enumerate.
 *
 * @property int $id
 * @property int $organization_id
 * @property string $email
 * @property string $role
 * @property string $token
 * @property int|null $invited_by_admin_id
 * @property int|null $invited_by_user_id
 * @property Carbon $expires_at
 * @property Carbon|null $accepted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['organization_id', 'email', 'role', 'token', 'invited_by_admin_id', 'invited_by_user_id', 'expires_at'])]
class OrganizationInvitation extends Model
{
    /** @use HasFactory<OrganizationInvitationFactory> */
    use HasFactory, HasHashId;

    /**
     * The organization the invitation grants access to.
     *
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * The admin who issued the invitation, if they still exist.
     *
     * @return BelongsTo<Admin, $this>
     */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'invited_by_admin_id');
    }

    /**
     * The company user who issued the invitation, if they still exist.
     *
     * @return BelongsTo<User, $this>
     */
    public function invitedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    /**
     * Whether the invitation has already been used.
     */
    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    /**
     * Whether the invitation is past its expiry.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Whether the invitation can still be accepted.
     */
    public function isPending(): bool
    {
        return ! $this->isAccepted() && ! $this->isExpired();
    }

    /**
     * Limit the query to invitations that can still be accepted.
     *
     * @param  Builder<$this>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->whereNull('accepted_at')->where('expires_at', '>', now());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory<self>
     */
    protected static function newFactory(): Factory
    {
        return OrganizationInvitationFactory::new();
    }
}
