<?php

namespace App\Modules\Boards\Models;

use App\Modules\Boards\Database\Factories\FieldFactory;
use App\Modules\Boards\Enums\FieldType;
use App\Modules\Core\Concerns\BelongsToOrganization;
use App\Modules\Core\Concerns\HasHashId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * One typed column on a board, defined once and carried by every node on it.
 *
 * The name is authored, so it is written in the organization's working language
 * and not translated per reader. A node stores its value for this field under
 * the field's **id** rather than its name, so renaming does not orphan every
 * value already recorded under the old one.
 *
 * @property int $id
 * @property int $organization_id
 * @property int $board_id
 * @property string $name
 * @property FieldType $type
 * @property list<string>|null $options
 * @property string|null $help
 * @property bool $is_required
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['board_id', 'name', 'type', 'options', 'help', 'is_required', 'position'])]
class Field extends Model
{
    /** @use HasFactory<FieldFactory> */
    use BelongsToOrganization, HasFactory, HasHashId, SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'board_fields';

    /**
     * The board the field describes.
     *
     * @return BelongsTo<Board, $this>
     */
    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    /**
     * The key this field's values are stored under on a node.
     *
     * Its id, as a string, because JSON object keys are strings.
     */
    public function valueKey(): string
    {
        return (string) $this->getKey();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => FieldType::class,
            'options' => 'array',
            'is_required' => 'boolean',
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
        return FieldFactory::new();
    }
}
