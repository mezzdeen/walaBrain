<?php

namespace App\Modules\Core\Support;

use App\Modules\Core\Models\PlatformSetting;
use Illuminate\Support\Facades\Cache;

/**
 * The platform's own settings, as opposed to an organization's or a user's.
 *
 * Every read goes through here rather than through the model, for two reasons.
 * The defaults live in code, so a database that has never been written to
 * answers the same as one that has — a fresh install is closed to sign-ups, the
 * behaviour the application had when that was hard coded. And the whole table
 * is cached as a single entry: these are read on requests that have nothing to
 * do with administering them (every login page render asks whether sign-up is
 * open), and that should not cost a query each.
 */
final class PlatformSettings
{
    /**
     * Whether anyone may create an account without being invited.
     */
    public const RegistrationOpen = 'registration_open';

    /**
     * Which third party identity providers may be used to sign in.
     */
    public const SocialProviders = 'social_providers';

    /**
     * The cache entry holding every setting.
     */
    private const CacheKey = 'platform-settings';

    /**
     * What the platform does before anyone has settled these.
     *
     * Closed, and no providers: the safe reading of an unanswered question, and
     * the behaviour that was hard coded before this table existed.
     *
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            self::RegistrationOpen => false,
            self::SocialProviders => ['google' => false],
        ];
    }

    /**
     * Every setting, with anything unset falling back to its default.
     *
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        $stored = Cache::rememberForever(self::CacheKey, fn (): array => PlatformSetting::query()
            ->pluck('value', 'key')
            ->all());

        return [...self::defaults(), ...$stored];
    }

    /**
     * Whether an uninvited visitor may create an account.
     */
    public static function registrationIsOpen(): bool
    {
        return (bool) (self::all()[self::RegistrationOpen] ?? false);
    }

    /**
     * The identity providers, each mapped to whether it is enabled.
     *
     * Keyed off the defaults rather than off what is stored, so a provider the
     * application has since stopped supporting cannot be re-introduced by a
     * stale row, and a newly supported one appears switched off rather than
     * missing.
     *
     * @return array<string, bool>
     */
    public static function socialProviders(): array
    {
        $stored = self::all()[self::SocialProviders];
        $stored = is_array($stored) ? $stored : [];

        $providers = [];

        foreach (array_keys(self::defaults()[self::SocialProviders]) as $provider) {
            $providers[$provider] = (bool) ($stored[$provider] ?? false);
        }

        return $providers;
    }

    /**
     * Store the given settings and drop the cache.
     *
     * Only the keys passed are touched, so a caller may save one switch without
     * having to send the rest back with it.
     *
     * @param  array<string, mixed>  $values
     */
    public static function update(array $values): void
    {
        foreach ($values as $key => $value) {
            PlatformSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget(self::CacheKey);
    }
}
