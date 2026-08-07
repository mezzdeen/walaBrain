<?php

namespace App\Modules\Core\Models;

use App\Modules\Core\Concerns\BelongsToOrganization;
use App\Modules\Core\Concerns\HasActivityTimeline;
use App\Modules\Core\Concerns\HasHashId;
use App\Modules\Core\Database\Factories\SpaceFactory;
use App\Modules\Core\Enums\SpaceAccess;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A container inside an organization for a related set of boards, usually one
 * team or process area.
 *
 * Who may reach it is decided by membership rather than by the roles someone
 * holds, so a person can be given a role once and then be let into the spaces
 * they actually work in. The exception is the default space, which every member
 * reaches without being added — see {@see self::accessFor()}.
 *
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property int $position
 * @property bool $is_default
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['name', 'position', 'is_default'])]
class Space extends Model
{
    /** @use HasFactory<SpaceFactory> */
    use BelongsToOrganization, HasActivityTimeline, HasFactory, HasHashId, SoftDeletes;

    /**
     * The people added to the space, and what each may do there.
     *
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('access')
            ->withTimestamps();
    }

    /**
     * What the user may do here, or null if the space is not theirs to reach.
     *
     * The default space answers for every member of the organization without a
     * membership row. Keeping that implicit rather than adding everyone to it on
     * the way in means there is nothing to keep in step: a member added by an
     * invitation, by the platform, or by a seeder reaches it the same way, and
     * no path can forget to.
     */
    public function accessFor(User $user): ?SpaceAccess
    {
        if ($this->is_default) {
            return $user->belongsToOrganization($this->organization_id)
                ? SpaceAccess::Edit
                : null;
        }

        $access = $this->members()
            ->whereKey($user->getKey())
            ->value('access');

        return is_string($access) ? SpaceAccess::tryFrom($access) : null;
    }

    /**
     * Whether the space is structural and may not be deleted.
     *
     * Removing the default would leave work that belongs to no process with
     * nowhere to live, and every member without a space they can reach.
     */
    public function isProtected(): bool
    {
        return $this->is_default;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'position' => 'integer',
        ];
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory<self>
     */
    protected static function newFactory(): Factory
    {
        return SpaceFactory::new();
    }
}
