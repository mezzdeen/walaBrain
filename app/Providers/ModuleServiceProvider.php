<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use RuntimeException;

/**
 * Discovers the modules the application is built from and registers each one.
 *
 * A module is a directory under `app/Modules` carrying a service provider named
 * after itself, and registering that provider is what makes it part of the
 * application. Discovered by scanning so the application never names a module:
 * dropping one in makes it work, and deleting one removes it.
 */
class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Register every installed module's own service provider.
     *
     * A single entry in `bootstrap/providers.php` rather than one per module,
     * for two reasons. Laravel silently drops a class it cannot find from that
     * file, so a module whose directory and provider disagree would be skipped
     * without a word; raised from here, it fails loudly the way a missing entry
     * in the morph map does. And the installers that add a provider rewrite
     * `bootstrap/providers.php` from its *evaluated* contents, which would turn
     * a scan performed there into whichever modules happened to exist that day.
     */
    public function register(): void
    {
        foreach (self::names() as $module) {
            $this->app->register(self::providerFor($module));
        }
    }

    /**
     * The directory every module lives in.
     *
     * Derived from this file's own location rather than from `app_path()`:
     * modules are looked up while the application is still being registered,
     * and the test bootstrap asks for them before there is a container at all.
     */
    public static function path(): string
    {
        return dirname(__DIR__).'/Modules';
    }

    /**
     * The name of every installed module, in registration order.
     *
     * @return list<string>
     */
    public static function names(): array
    {
        return array_map(
            fn (string $directory): string => basename($directory),
            glob(self::path().'/*', GLOB_ONLYDIR) ?: [],
        );
    }

    /**
     * The service provider a module is required to carry.
     *
     * @return class-string<ServiceProvider>
     *
     * @throws RuntimeException when the module does not have one
     */
    public static function providerFor(string $module): string
    {
        /** @var class-string<ServiceProvider> $provider */
        $provider = 'App\\Modules\\'.$module.'\\'.$module.'ServiceProvider';

        if (! class_exists($provider)) {
            throw new RuntimeException(
                "The [{$module}] module has no service provider. Expected [{$provider}] in ".self::path()."/{$module}.",
            );
        }

        return $provider;
    }
}
