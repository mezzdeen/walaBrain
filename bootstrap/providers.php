<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use Illuminate\Support\ServiceProvider;

/**
 * Every module's service provider, discovered rather than listed.
 *
 * A module is a directory under `app/Modules` holding a provider named after
 * itself, and registering one is what makes it part of the application. Found
 * by scanning so that the application never names a module: dropping a module
 * in makes it work, and deleting one removes it without editing this file.
 *
 * `__DIR__` rather than `app_path()`: this file is read while the application
 * is still being configured, before the container can resolve paths.
 *
 * @var list<class-string<ServiceProvider>> $modules
 */
$modules = array_map(
    fn (string $path): string => 'App\\Modules\\'.basename(dirname($path)).'\\'.basename($path, '.php'),
    glob(__DIR__.'/../app/Modules/*/*ServiceProvider.php') ?: [],
);

// Modules last, so a module can build on what the application has registered.
return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    ...$modules,
];
