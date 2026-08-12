<?php

namespace App\Modules\Flows\Models;

use App\Modules\Boards\Models\Board;
use App\Modules\Core\Concerns\BelongsToOrganization;
use App\Modules\Core\Concerns\HasHashId;
use App\Modules\Flows\Database\Factories\FlowFactory;
use App\Modules\Forms\Models\Form;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * What happens after this flow's form is submitted: a fixed sequence of steps,
 * each executed for every submission as its own run.
 *
 * @property int $id
 * @property int $organization_id
 * @property int $board_id
 * @property int $form_id
 * @property string $name
 * @property int $version
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['organization_id', 'board_id', 'form_id', 'name', 'version', 'published_at'])]
class Flow extends Model
{
    /** @use HasFactory<FlowFactory> */
    use BelongsToOrganization, HasFactory, HasHashId, SoftDeletes;

    /**
     * The board whose nodes the flow acts on.
     *
     * @return BelongsTo<Board, $this>
     */
    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    /**
     * The form whose submission triggers a run.
     *
     * @return BelongsTo<Form, $this>
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * The sequence, in the order it executes.
     *
     * @return HasMany<FlowStep, $this>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(FlowStep::class)->orderBy('position');
    }

    /**
     * Whether the flow responds to submissions.
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
        return FlowFactory::new();
    }
}
