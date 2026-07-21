<?php

namespace App\Modules\Core\Concerns;

use App\Modules\Core\Support\HashId;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Addresses a model in URLs by a short opaque code instead of its primary key.
 *
 * A sequential key in a link tells anyone holding one account how many
 * organizations exist, in what order they signed up and how fast that number
 * grows, and invites walking the neighbours by hand. The code derived by
 * {@see HashId} says none of that.
 *
 * The primary key itself does not change: it stays an auto-incrementing
 * integer, so foreign keys, pivot tables and the permission package's rows are
 * untouched. The code is computed, not stored — there is no column and no
 * second unique index.
 *
 * Two consequences worth knowing before adding this to a model:
 *
 * - The integer is hidden from serialization, not from PHP. `getKey()`,
 *   `$model->id`, `only(['id'])` and `getAttributes()` all still return it,
 *   which is why the permission package's cache and the session-held
 *   organization context carry on working unchanged.
 * - A code needs the key to exist. A query that selects a column list without
 *   `id` yields models whose `hash_id` is null and whose links go nowhere, so
 *   those lists have to keep selecting it.
 *
 * This obscures identifiers; it does not authorize anything. Five characters is
 * a space a script can walk, so every route still runs its policy.
 *
 * @phpstan-require-extends Model
 */
trait HasHashId
{
    /**
     * Send the code to the client, and keep the integer at home.
     *
     * Done here rather than through `#[Appends]` and `#[Hidden]` because those
     * attributes do not merge down a hierarchy — Eloquent takes the first one
     * it finds — so a model already declaring either would have to remember to
     * extend it by hand. Redeclaring `$appends` as a trait property is not an
     * option either: the framework already declares it, and the collision is a
     * fatal error.
     */
    public function initializeHasHashId(): void
    {
        $this->mergeAppends(['hash_id']);
        $this->mergeHidden(['id']);
    }

    /**
     * The public code for this record.
     *
     * @return Attribute<string|null, never>
     */
    protected function hashId(): Attribute
    {
        return Attribute::get(fn (mixed $value, array $attributes): ?string => HashId::encode($this->getKey()));
    }

    /**
     * Bind this model in routes by its code.
     */
    public function getRouteKeyName(): string
    {
        return 'hash_id';
    }

    /**
     * Resolve a bound code back to the record it names.
     *
     * Overriding the query rather than `resolveRouteBinding()` itself leaves the
     * framework's two wrappers around it intact — soft-deleted bindings, and the
     * child lookup behind `->scopeBindings()` — so reaching for either later
     * works instead of silently doing nothing.
     *
     * A code that was never one of ours resolves to no record rather than to an
     * error, which is the same 404 a real but unknown code gets. Telling the two
     * apart would answer the question this whole trait exists to refuse.
     *
     * @param  Model|Builder<static>|Relation<*, *, *>  $query
     * @return BuilderContract
     */
    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        if ($field !== null && $field !== $this->getRouteKeyName()) {
            return parent::resolveRouteBindingQuery($query, $value, $field);
        }

        $key = HashId::decode((string) $value);

        return $key === null
            ? $query->whereRaw('1 = 0')
            : $query->whereKey($key);
    }
}
