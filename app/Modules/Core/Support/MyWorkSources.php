<?php

namespace App\Modules\Core\Support;

use App\Modules\Core\Models\User;

/**
 * What each module contributes to a person's My Work screen.
 *
 * My Work is every task and approval waiting on one person, but tasks belong
 * to one module and approvals to another, and the module that renders the
 * screen must not need to know which others are installed. So each module
 * registers a source from its own provider, the screen collects them all, and
 * deleting a module's directory removes its items the same way it removes its
 * routes.
 */
final class MyWorkSources
{
    /**
     * The registered sources, keyed by the prop each fills.
     *
     * @var array<string, callable(User): mixed>
     */
    private static array $sources = [];

    /**
     * Contribute a source. The key becomes a prop on the My Work page, so a
     * module registering `approvals` decides what that prop holds.
     *
     * @param  callable(User): mixed  $source
     */
    public static function register(string $key, callable $source): void
    {
        self::$sources[$key] = $source;
    }

    /**
     * Everything every installed module has waiting on this person.
     *
     * @return array<string, mixed>
     */
    public static function collect(User $user): array
    {
        $items = [];

        foreach (self::$sources as $key => $source) {
            $items[$key] = $source($user);
        }

        return $items;
    }

    /**
     * Forget every source, so one test's registrations cannot leak into the
     * next: providers register these once per process, but tests re-register.
     */
    public static function flush(): void
    {
        self::$sources = [];
    }
}
