<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Http\Request;

/**
 * The application's own shared props, tested without any module in the way.
 *
 * These are what keeps the shell renderable when nothing richer supplies them.
 * The front end reads several of these without guarding, so a missing key is a
 * page that renders against `undefined` rather than a page that renders plainly.
 */
function sharedByTheApplication(): array
{
    $request = Request::create('/');
    $request->setLaravelSession(app('session.store'));

    return (new HandleInertiaRequests)->share($request);
}

test('the application answers every prop the front end reads unguarded', function () {
    expect(sharedByTheApplication())->toHaveKeys([
        'name',
        'auth',
        'sidebarOpen',
        'locale',
        'direction',
        'supportedLocales',
        'translations',
        'permissions',
        'organization',
        'organizations',
        'registration',
        'brandColorCss',
    ]);
});

test('the application defaults deny everything and claim no organization', function () {
    $shared = sharedByTheApplication();

    expect($shared['permissions'])->toBe([])
        ->and($shared['organization'])->toBeNull()
        ->and($shared['organizations'])->toBe([])
        ->and($shared['registration'])->toBeNull()
        ->and($shared['brandColorCss'])->toBe('')
        ->and($shared['auth']['admin'])->toBeNull();
});

test('the application language props agree with one another', function () {
    $shared = sharedByTheApplication();

    expect($shared['locale'])->toBe(config('app.locale'))
        ->and($shared['supportedLocales'])->toBe([config('app.locale')])
        ->and($shared['direction'])->toBe('ltr');
});
