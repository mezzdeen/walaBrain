<?php

namespace App\Modules\Boards\Models;

use App\Modules\Boards\Database\Factories\GroupFactory;
use App\Modules\Core\Concerns\BelongsToOrganization;
use App\Modules\Core\Concerns\HasHashId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A visual partition of a board's nodes, usually one per stage of work.
 *
 * Display only: every node in every group on a board carries the same fields,
 * and moving one between groups changes nothing else about it.
 *
 * @property int $id
 * @property int $organization_id
 * @property int $board_id
 * @property string $name
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['organization_id', 'board_id', 'name', 'position'])]
class Group extends Model
{
    /** @use HasFactory<GroupFactory> */
    use BelongsToOrganization, HasFactory, HasHashId, SoftDeletes;

    /**
     * The board the group partitions.
     *
     * @return BelongsTo<Board, $this>
     */
    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    /**
     * The nodes currently displayed in this group.
     *
     * @return HasMany<Node, $this>
     */
    public function nodes(): HasMany
    {
        return $this->hasMany(Node::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
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
        return GroupFactory::new();
    }
}
