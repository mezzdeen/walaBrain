<?php

namespace App\Modules\Core\Support;

use App\Modules\Core\Concerns\HasHashId;
use Sqids\Sqids;

/**
 * Turns a record's primary key into the short opaque code that addresses it in
 * a URL, and back again.
 *
 * Nothing is stored: a code is a reversible function of the key, so there is no
 * column to add, no backfill to run, and no second unique index to maintain.
 * The primary key stays an auto-incrementing integer, which is what every
 * foreign key, pivot table and permission row in the application already
 * depends on.
 *
 * This obscures identifiers; it does not protect records. Five characters is a
 * space a script can walk, so authorization stays where it was — every route
 * still runs its policy. What this buys is that a URL no longer discloses how
 * many organizations exist or in what order they arrived, and that guessing the
 * next one by hand stops working.
 *
 * Applied to models through {@see HasHashId}.
 */
final class HashId
{
    /**
     * The last encoder built, alongside the settings it was built from.
     *
     * @var array{string, int, Sqids}|null
     */
    private static ?array $memo = null;

    /**
     * The public code for a primary key, or null when the model has none.
     *
     * The null case is real rather than defensive: a query that selects a
     * column list without `id` produces models whose key is null, and encoding
     * that would hand every row the same code.
     *
     * @return ($key is null ? null : string)
     */
    public static function encode(?int $key): ?string
    {
        if ($key === null) {
            return null;
        }

        return self::sqids()->encode([$key]);
    }

    /**
     * The primary key behind a code, or null when the code is not one we issued.
     *
     * Decoding alone is not enough to accept a value. Sqids will happily read
     * strings this application would never produce, so the result is encoded
     * again and compared: only the exact form we would have issued for that key
     * is honoured, which keeps one record reachable at one URL.
     */
    public static function decode(string $code): ?int
    {
        $decoded = self::sqids()->decode($code);

        if (count($decoded) !== 1) {
            return null;
        }

        return self::sqids()->encode($decoded) === $code
            ? $decoded[0]
            : null;
    }

    /**
     * The configured encoder, rebuilt only when the settings behind it change.
     *
     * Deliberately not resolved from the container. `Sqids` is a concrete class
     * whose every constructor argument has a default, so an unbound
     * `app(Sqids::class)` does not fail — it quietly returns an encoder using
     * the library's published alphabet, and every code it issues is reversible
     * by anyone who recognises the format. Nothing throws and nothing looks
     * wrong. Building it here removes the question of whether some provider
     * registered a binding first.
     *
     * Memoized against the settings rather than held outright, so a test that
     * changes either one gets an encoder that agrees with it rather than a
     * stale instance from an earlier case.
     */
    private static function sqids(): Sqids
    {
        $alphabet = (string) config('core.hash_id.alphabet');
        $minLength = (int) config('core.hash_id.min_length');

        if (self::$memo === null || self::$memo[0] !== $alphabet || self::$memo[1] !== $minLength) {
            self::$memo = [$alphabet, $minLength, new Sqids(
                alphabet: $alphabet,
                minLength: $minLength,
                // Off on purpose: it keeps codes from spelling something
                // unfortunate by re-encoding with a growing offset, which makes
                // a code's width vary and throws outright once the offsets run
                // out. Both are worse than the occasional unlucky code.
                blocklist: [],
            )];
        }

        return self::$memo[2];
    }
}
