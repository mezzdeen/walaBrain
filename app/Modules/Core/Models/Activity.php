<?php

namespace App\Modules\Core\Models;

use App\Modules\Core\Concerns\BelongsToOrganization;
use App\Modules\Core\Concerns\HasActivityTimeline;
use App\Modules\Core\Database\Factories\ActivityFactory;
use App\Modules\Core\Exceptions\ActivityIsAppendOnly;
use App\Modules\Core\Support\Platform;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * One entry on a record's activity timeline.
 *
 * The application's memory: who did what, to what, and when. Entries are only
 * ever added — a correction is a new entry describing the correction, never an
 * edit to the one that was wrong — so the timeline can be trusted as an account
 * of what happened rather than of what someone last decided it should say.
 *
 * Written through {@see HasActivityTimeline} rather than constructed directly,
 * so the subject, the organization and the actor cannot disagree.
 *
 * @property int $id
 * @property int $organization_id
 * @property string $subject_type
 * @property int $subject_id
 * @property string|null $actor_type
 * @property int|null $actor_id
 * @property string $type
 * @property array<string, mixed>|null $payload
 * @property Carbon|null $created_at
 * @property-read Model $subject
 * @property-read Model|null $actor
 */
#[Fillable(['subject_type', 'subject_id', 'actor_type', 'actor_id', 'type', 'payload'])]
class Activity extends Model
{
    /** @use HasFactory<ActivityFactory> */
    use BelongsToOrganization, HasFactory;

    /**
     * An entry is never updated, so there is nothing for a second timestamp to
     * record that `created_at` does not already say.
     */
    public const UPDATED_AT = null;

    /**
     * Refuse to change or remove an entry.
     *
     * The database refuses this too, and that is the guarantee that actually
     * holds — a model is stepped around the moment anyone writes a query
     * builder statement. This is here so the ordinary path fails in PHP with an
     * explanation, rather than as a driver error raised by a trigger.
     */
    protected static function booted(): void
    {
        static::updating(function (): never {
            throw ActivityIsAppendOnly::updated();
        });

        static::deleting(function (): never {
            throw ActivityIsAppendOnly::deleted();
        });
    }

    /**
     * The record the entry is about.
     *
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Who acted, where anyone did.
     *
     * Null for work nobody triggered: a tentative hold that expired on its own,
     * a run that resumed because a date arrived.
     *
     * @return MorphTo<Model, $this>
     */
    public function actor(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The identity to credit for whatever is happening right now.
     *
     * Resolved from the platform serving the request rather than from whichever
     * guard happens to be authenticated, for the reason given in
     * {@see Platform::isAdmin()}: a browser can hold a session on both
     * platforms at once, and crediting an admin for something a user did on the
     * company platform would put a name to an action they did not take.
     *
     * Null outside a request — a queued job or a scheduled command acts for the
     * system, and inventing an actor there would be a lie the timeline then
     * tells forever. Nothing special is needed for that case: a console request
     * matches neither platform's path, and no guard is authenticated there.
     */
    public static function currentActor(): ?Model
    {
        $guard = Platform::isAdmin(request()) ? 'super' : 'web';

        $actor = Auth::guard($guard)->user();

        return $actor instanceof Model ? $actor : null;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory<self>
     */
    protected static function newFactory(): Factory
    {
        return ActivityFactory::new();
    }
}
