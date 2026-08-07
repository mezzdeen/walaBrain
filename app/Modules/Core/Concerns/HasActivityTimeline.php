<?php

namespace App\Modules\Core\Concerns;

use App\Modules\Core\Models\Activity;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Gives a model an append-only record of what has happened to it.
 *
 * The one way anything writes to a timeline. Recording through the subject
 * rather than by constructing an {@see Activity} means the entry cannot end up
 * pointing at the wrong record, or at a different organization from the one
 * that owns it.
 *
 * Entry types are strings rather than one shared enum: every module has its own
 * vocabulary — a decision, a booking, a move between groups — and a single enum
 * in Core would have to be edited every time a module invented a word for
 * something Core knows nothing about. Modules declare their own enum and pass
 * it; the signature accepts one directly.
 *
 * @phpstan-require-extends Model
 */
trait HasActivityTimeline
{
    /**
     * Everything that has happened to this record, most recent first.
     *
     * Ordered by key rather than by `created_at`: entries written in the same
     * second are common — a submission and the run it starts — and only
     * insertion order says which came first.
     *
     * @return MorphMany<Activity, $this>
     */
    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject')->latest('id');
    }

    /**
     * Add an entry to this record's timeline.
     *
     * The actor defaults to whoever is acting on the current request, which is
     * right almost everywhere. Pass one explicitly only when the obvious answer
     * is wrong: work done on someone's behalf, or a job that knows who asked
     * for it. Passing null deliberately records the system as the actor.
     *
     * @param  array<string, mixed>  $payload
     */
    public function recordActivity(
        string|BackedEnum $type,
        array $payload = [],
        Model|false|null $actor = false,
    ): Activity {
        // `false` means "nobody said", so resolve it; null means "the system
        // did this", which is a real answer and must not be overridden. The two
        // are otherwise indistinguishable in a nullable parameter.
        $actor = $actor === false ? Activity::currentActor() : $actor;

        return $this->activities()->create([
            'type' => $type instanceof BackedEnum ? $type->value : $type,
            'payload' => $payload === [] ? null : $payload,
            'actor_type' => $actor?->getMorphClass(),
            'actor_id' => $actor?->getKey(),
        ]);
    }
}
