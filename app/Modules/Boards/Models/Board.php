<?php

namespace App\Modules\Boards\Models;

use App\Modules\Boards\Database\Factories\BoardFactory;
use App\Modules\Core\Concerns\BelongsToOrganization;
use App\Modules\Core\Concerns\HasActivityTimeline;
use App\Modules\Core\Concerns\HasHashId;
use App\Modules\Core\Models\Space;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * One process's worth of work: the fields its nodes carry, the groups they are
 * partitioned into, and the nodes themselves.
 *
 * Who can reach it is not asked here. A board lives in a space, and space
 * membership is the whole of the answer — see {@see BoardPolicy}.
 *
 * @property int $id
 * @property int $organization_id
 * @property int $space_id
 * @property string $name
 * @property int $position
 * @property bool $is_default
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['space_id', 'name', 'position', 'is_default'])]
class Board extends Model
{
    /** @use HasFactory<BoardFactory> */
    use BelongsToOrganization, HasActivityTimeline, HasFactory, HasHashId, SoftDeletes;

    /**
     * The space the board lives in, which decides who can reach it.
     *
     * @return BelongsTo<Space, $this>
     */
    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    /**
     * The board's field schema, in the order it is laid out.
     *
     * @return HasMany<Field, $this>
     */
    public function fields(): HasMany
    {
        return $this->hasMany(Field::class)->orderBy('position');
    }

    /**
     * The partitions the board's nodes are displayed in.
     *
     * @return HasMany<Group, $this>
     */
    public function groups(): HasMany
    {
        return $this->hasMany(Group::class)->orderBy('position');
    }

    /**
     * Everything on the board.
     *
     * @return HasMany<Node, $this>
     */
    public function nodes(): HasMany
    {
        return $this->hasMany(Node::class);
    }

    /**
     * Whether the board is structural and may not be deleted.
     *
     * Removing the default would leave work that belongs to no process with
     * nowhere to live.
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
        return BoardFactory::new();
    }
}
