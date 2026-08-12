<?php

namespace App\Modules\Boards\Models;

use App\Modules\Boards\Database\Factories\NodeFactory;
use App\Modules\Boards\Enums\FieldType;
use App\Modules\Core\Concerns\BelongsToOrganization;
use App\Modules\Core\Concerns\HasActivityTimeline;
use App\Modules\Core\Concerns\HasHashId;
use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * The universal work item: a request, a task, a booking, or any other tracked
 * record.
 *
 * One table for all of them, and one for however a node came to exist —
 * somebody wrote it for themselves, their manager wrote it for them, or a flow
 * generated it. Three origins, one record, so My Work is a single query rather
 * than a union of everything that can produce work.
 *
 * Values are split between columns and JSON on purpose. What every node has,
 * and what is queried across boards, is a column: the title, the assignee, the
 * due date, the status. What a Process Designer invented for one board lives in
 * `values`, keyed by field id, because there is no column to give a field that
 * did not exist when the table was made.
 *
 * @property int $id
 * @property int $organization_id
 * @property int $board_id
 * @property int|null $group_id
 * @property string $title
 * @property string|null $reference
 * @property string|null $description
 * @property int|null $assignee_id
 * @property int|null $creator_id
 * @property Carbon|null $due_date
 * @property string|null $status
 * @property Carbon|null $completed_at
 * @property array<string, mixed>|null $values
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['board_id', 'group_id', 'title', 'reference', 'description', 'assignee_id', 'creator_id', 'due_date', 'status', 'values'])]
class Node extends Model
{
    /** @use HasFactory<NodeFactory> */
    use BelongsToOrganization, HasActivityTimeline, HasFactory, HasHashId, SoftDeletes;

    /**
     * The board the node lives on, which owns the fields it carries.
     *
     * @return BelongsTo<Board, $this>
     */
    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    /**
     * The partition the node is currently displayed in.
     *
     * @return BelongsTo<Group, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Who is responsible for the node.
     *
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * Who brought the node into existence, where anyone personally did.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * The node's value for one of its board's fields.
     */
    public function valueFor(Field $field): mixed
    {
        return ($this->values ?? [])[$field->valueKey()] ?? null;
    }

    /**
     * Record the node's value for one of its board's fields.
     *
     * Written through the field rather than by key, so the value lands under
     * the field's id and in the shape its type says it should be — see
     * {@see FieldType::storedAs()}. Assigning to `values` directly is what
     * eventually puts a formatted number in a column somebody later sorts.
     */
    public function setValueFor(Field $field, mixed $value): self
    {
        $values = $this->values ?? [];
        $values[$field->valueKey()] = $value;

        $this->values = $values;

        return $this;
    }

    /**
     * Constrain to the nodes waiting on one person, soonest first.
     *
     * What My Work is: everything assigned to somebody across every board in
     * the business line they are working in, rather than a board at a time.
     * Nodes with no due date sort last, since an undated item is not more
     * urgent than a dated one.
     *
     * @param  Builder<Node>  $query
     * @return Builder<Node>
     */
    public function scopeAssignedTo(Builder $query, User $user): Builder
    {
        return $query->where('assignee_id', $user->getKey())
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->orderBy('id');
    }

    /**
     * Constrain to the work still to be done.
     *
     * @param  Builder<Node>  $query
     * @return Builder<Node>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('completed_at');
    }

    /**
     * Whether the node's due date has passed without it being finished.
     *
     * Overdue is a flag on the current state rather than a stage of its own: an
     * item can be overdue whether it has been started or not, and completing it
     * late clears the flag without erasing that it was late — the timeline
     * keeps that.
     */
    public function isOverdue(): bool
    {
        return $this->completed_at === null
            && $this->due_date !== null
            && $this->due_date->isPast();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'values' => 'array',
        ];
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory<self>
     */
    protected static function newFactory(): Factory
    {
        return NodeFactory::new();
    }
}
