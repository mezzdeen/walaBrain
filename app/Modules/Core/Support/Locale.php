<?php

namespace App\Modules\Core\Support;

use Illuminate\Support\Facades\File;

/**
 * Single source of truth for the application's locale metadata.
 *
 * Every other part of the i18n stack (the SetLocale middleware, the shared
 * Inertia props, the root template and the translation endpoint) resolves
 * supported locales, text direction and the cache-busting version here.
 */
class Locale
{
    /**
     * Locales the application ships translations for.
     *
     * @var list<string>
     */
    public const SUPPORTED = ['en', 'ar'];

    /**
     * Locales that are written from right to left.
     *
     * @var list<string>
     */
    public const RIGHT_TO_LEFT = ['ar', 'he', 'fa', 'ur'];

    /**
     * Memoized translation version for the current request.
     */
    protected static ?string $version = null;

    /**
     * Determine whether the application ships translations for the locale.
     */
    public static function isSupported(?string $locale): bool
    {
        return $locale !== null && in_array($locale, self::SUPPORTED, true);
    }

    /**
     * The locale used when nothing else can be resolved.
     */
    public static function default(): string
    {
        $locale = config('app.locale');

        return self::isSupported($locale) ? $locale : self::SUPPORTED[0];
    }

    /**
     * The text direction for the given locale.
     */
    public static function direction(string $locale): string
    {
        return in_array($locale, self::RIGHT_TO_LEFT, true) ? 'rtl' : 'ltr';
    }

    /**
     * Directories that hold translation files, in load order.
     *
     * @return list<string>
     */
    protected static function paths(): array
    {
        return [lang_path(), __DIR__.'/../lang'];
    }

    /**
     * A short hash that changes whenever any translation file changes.
     *
     * Drives the client-side dictionary cache and the endpoint's ETag, so a
     * new key shows up immediately in development and after every deploy.
     */
    public static function version(): string
    {
        if (self::$version !== null) {
            return self::$version;
        }

        $timestamp = 0;
        $count = 0;

        foreach (self::paths() as $path) {
            if (! is_dir($path)) {
                continue;
            }

            foreach (File::allFiles($path) as $file) {
                $timestamp = max($timestamp, $file->getMTime());
                $count++;
            }
        }

        return self::$version = substr(md5($timestamp.':'.$count), 0, 8);
    }
}
