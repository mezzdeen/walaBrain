<?php

namespace App\Modules\Core\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Compiles the translation dictionary handed to the client.
 *
 * Shared by the Inertia middleware, which ships the dictionary as a once-prop
 * so server-side rendering and the client hydrate from the same strings.
 */
class Translations
{
    /**
     * The dictionary for a locale, merged over the fallback locale.
     *
     * @return array<string, mixed>
     */
    public static function for(string $locale): array
    {
        if (app()->isLocal()) {
            return self::compile($locale);
        }

        return Cache::remember(
            "translations.{$locale}.".Locale::version(),
            now()->addDay(),
            fn (): array => self::compile($locale),
        );
    }

    /**
     * Merge the locale's messages over the fallback locale's, mirroring the
     * per-key fallback that `__()` performs on the server.
     *
     * @return array<string, mixed>
     */
    protected static function compile(string $locale): array
    {
        $messages = self::load($locale);
        $fallback = config('app.fallback_locale');

        if (is_string($fallback) && $fallback !== $locale) {
            $messages = array_replace_recursive(self::load($fallback), $messages);
        }

        return $messages;
    }

    /**
     * Load every translation group registered for a locale.
     *
     * Root files land at the top level (`validation.required`) while module
     * namespaces are nested under their namespace (`core.common.save`), so any
     * module registering translations is picked up without further wiring.
     *
     * @return array<string, mixed>
     */
    protected static function load(string $locale): array
    {
        $messages = self::loadDirectory(lang_path($locale));

        // Modules register their namespace through `loadTranslationsFrom`,
        // which defers until the translator is resolved. Resolving it here
        // guarantees module namespaces are present even when nothing else in
        // the request has translated a string yet.
        /** @var array<string, string> $namespaces */
        $namespaces = app('translator')->getLoader()->namespaces();

        foreach ($namespaces as $namespace => $path) {
            $groups = self::loadDirectory($path.DIRECTORY_SEPARATOR.$locale);

            if ($groups !== []) {
                $messages[$namespace] = $groups;
            }
        }

        return $messages;
    }

    /**
     * Load every PHP translation file in a directory, keyed by file name.
     *
     * @return array<string, mixed>
     */
    protected static function loadDirectory(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $messages = [];

        /** @var SplFileInfo $file */
        foreach (File::files($directory) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $messages[$file->getFilenameWithoutExtension()] = require $file->getPathname();
        }

        return $messages;
    }
}
