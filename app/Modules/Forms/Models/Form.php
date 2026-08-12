<?php

namespace App\Modules\Forms\Models;

use App\Modules\Boards\Models\Board;
use App\Modules\Core\Concerns\BelongsToOrganization;
use App\Modules\Core\Concerns\HasHashId;
use App\Modules\Forms\Database\Factories\FormFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * An intake surface: submitting it creates a node on its board, carrying the
 * entered values and a reference number under this form's prefix.
 *
 * @property int $id
 * @property int $organization_id
 * @property int $board_id
 * @property string $name
 * @property string $prefix
 * @property int $version
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['board_id', 'name', 'prefix', 'version', 'published_at'])]
class Form extends Model
{
    /** @use HasFactory<FormFactory> */
    use BelongsToOrganization, HasFactory, HasHashId, SoftDeletes;

    /**
     * The board a submission creates its node on.
     *
     * @return BelongsTo<Board, $this>
     */
    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    /**
     * Whether the form is accepting submissions.
     */
    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory<self>
     */
    protected static function newFactory(): Factory
    {
        return FormFactory::new();
    }
}
