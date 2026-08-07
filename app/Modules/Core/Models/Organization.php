<?php

namespace App\Modules\Core\Models;

use App\Modules\Core\Concerns\HasActivityTimeline;
use App\Modules\Core\Concerns\HasHashId;
use App\Modules\Core\Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $color
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['name', 'color'])]
class Organization extends Model
{
    use HasActivityTimeline, HasHashId, SoftDeletes;

    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    /**
     * Discard outstanding invitations when the organization goes away.
     *
     * The database cascade only fires on a hard delete, and an invitation that
     * outlives its organization is a live link into a tenant that no longer
     * exists. Memberships and roles are deliberately left alone: those are what
     * a restore needs to put the organization back the way it was.
     */
    protected static function booted(): void
    {
        static::deleted(function (self $organization): void {
            if ($organization->isForceDeleting()) {
                return;
            }

            $organization->invitations()->delete();
        });
    }

    /**
     * The invitations issued to join the organization.
     *
     * @return HasMany<OrganizationInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(OrganizationInvitation::class);
    }

    /**
     * The users that belong to the organization.
     *
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory<self>
     */
    protected static function newFactory(): Factory
    {
        return OrganizationFactory::new();
    }
}
