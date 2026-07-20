<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetOrganizationContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
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
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        $middleware->web(append: [
            HandleAppearance::class,
            SetLocale::class,
            SetOrganizationContext::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Permission checks are meaningless until the active organization is
        // known, and route model binding may scope a lookup by it, so the
        // context has to be in place before bindings are substituted. Without
        // this a request that should be forbidden 404s instead. Injected into
        // the framework's list rather than replacing it, which would silently
        // drop the entries this file never mentions.
        $middleware->prependToPriorityList(
            before: SubstituteBindings::class,
            prepend: SetOrganizationContext::class,
        );

        // Send unauthenticated visitors to the login screen of the platform
        // they were trying to reach, keeping the two platforms isolated.
        $middleware->redirectGuestsTo(fn (Request $request): string => $request->is('super', 'super/*')
            ? route('super.login')
            : route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
