<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'locale', 'sidebar_state']);

        // Spatie's service provider registers Blade directives and commands but
        // not the middleware aliases, so they have to be declared here.
        // Module-owned aliases are not here: a module registers its own
        // alongside its routes and policies.
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        // Only what the application itself owns. A module appends its own
        // middleware from its service provider, so that nothing here has to be
        // edited when one is added or removed.
        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Send unauthenticated visitors to the login screen of the platform
        // they were trying to reach, keeping the two platforms isolated.
        // `Route::has`: the admin platform is a module's, and without it there
        // is only one login screen to send anyone to.
        $middleware->redirectGuestsTo(
            fn (Request $request): string => $request->is('super', 'super/*') && Route::has('super.login')
                ? route('super.login')
                : route('login'),
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
