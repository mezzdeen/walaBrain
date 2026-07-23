<?php

use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authorization coverage
|--------------------------------------------------------------------------
|
| Every controller action is authorized by a policy. This asserts it stays
| that way: a new controller cannot ship without either a policy check or a
| deliberate, reviewable entry in the exempt list below.
|
| An action counts as covered when it is behind `can:` middleware — which is
| what `authorizeResource()` registers — or calls `Gate::authorize()` in its
| own body.
|
*/

/**
 * Actions that are deliberately not authorized, and why.
 *
 * Each of these is reachable without an identity to authorize, so a policy
 * would deny every caller rather than the wrong ones.
 *
 * @var array<string, string>
 */
const UNAUTHORIZED_ACTIONS = [
    'Auth\AuthenticatedSessionController@create' => 'the super platform sign-in form',
    'Auth\AuthenticatedSessionController@store' => 'authorization here is the credential check itself',
    'Auth\AuthenticatedSessionController@destroy' => 'signing out is always allowed to the signed-in',
    'InvitationController@show' => 'guest facing: the signed token is the authorization',
    'InvitationController@store' => 'guest facing: the signed token is the authorization',
    'InvitationController@accept' => 'guest facing: the signed token and matching address are the authorization, which redirects rather than denies',
    'Auth\RegistrationController@create' => 'guest facing: the `registration` middleware is the authorization',
    'Auth\RegistrationController@store' => 'guest facing: the `registration` middleware is the authorization',
];

test('every controller action is authorized', function () {
    $unauthorized = [];

    foreach (Route::getRoutes()->getRoutes() as $route) {
        $action = controllerAction($route);

        if ($action === null || array_key_exists($action, UNAUTHORIZED_ACTIONS)) {
            continue;
        }

        if (! isAuthorized($route)) {
            $unauthorized[] = $action.'  ('.$route->uri().')';
        }
    }

    expect($unauthorized)->toBe(
        [],
        "These controller actions have no policy check. Add one, or list them in\n".
        "UNAUTHORIZED_ACTIONS with the reason they are safe without:\n  ".
        implode("\n  ", $unauthorized),
    );
});

test('the exempt list has no stale entries', function () {
    // An exemption that no longer matches a route is a rule nobody is reading
    // any more, and the next controller to reuse the name inherits it silently.
    $actions = [];

    foreach (Route::getRoutes()->getRoutes() as $route) {
        if (($action = controllerAction($route)) !== null) {
            $actions[$action] = true;
        }
    }

    expect(array_keys(array_diff_key(UNAUTHORIZED_ACTIONS, $actions)))->toBe([]);
});

/**
 * The route's controller action as `Class@method`, with the shared controller
 * namespace trimmed off, or null when the route is not handled by a controller.
 */
function controllerAction(RoutingRoute $route): ?string
{
    $action = $route->getActionName();

    if ($action === 'Closure' || ! str_contains($action, '@')) {
        return null;
    }

    [$class, $method] = explode('@', $action, 2);

    if (! str_starts_with($class, 'App\\')) {
        return null;
    }

    return preg_replace('/^App\\\\(Modules\\\\Core\\\\)?Http\\\\Controllers\\\\/', '', $class).'@'.$method;
}

/**
 * Whether the route is covered by `can:` middleware or its action calls
 * `Gate::authorize()`.
 */
function isAuthorized(RoutingRoute $route): bool
{
    foreach ($route->gatherMiddleware() as $middleware) {
        if (is_string($middleware) && str_starts_with($middleware, 'can:')) {
            return true;
        }
    }

    return callsGateAuthorize($route);
}

/**
 * Read the action's own source and look for a `Gate::authorize()` call.
 *
 * Source inspection rather than a runtime probe because the alternative is
 * exercising every route with a fixture identity, which needs a valid model
 * binding per route and would test the fixtures as much as the routes.
 */
function callsGateAuthorize(RoutingRoute $route): bool
{
    [$class, $method] = explode('@', $route->getActionName(), 2);

    try {
        $reflection = new ReflectionMethod($class, $method);
    } catch (ReflectionException) {
        return false;
    }

    $file = $reflection->getFileName();
    $start = $reflection->getStartLine();
    $end = $reflection->getEndLine();

    if ($file === false || $start === false || $end === false) {
        return false;
    }

    $lines = file($file);

    if ($lines === false) {
        return false;
    }

    $body = implode('', array_slice($lines, $start - 1, $end - $start + 1));

    return str_contains($body, 'Gate::authorize(');
}
