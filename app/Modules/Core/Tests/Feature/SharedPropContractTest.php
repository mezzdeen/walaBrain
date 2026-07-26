<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Modules\Core\Http\Middleware\ShareInertiaProps;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * The contract between the application's shared props and this module's.
 *
 * The module overwrites what the application shared, which only works while the
 * application has something to overwrite. A prop the module introduces on its
 * own is one the front end reads as `undefined` on any request this module does
 * not reach — and on every request at all, once the module is removed.
 */
test('the module introduces no prop the application has no default for', function () {
    $request = Request::create('/');
    $request->setLaravelSession(app('session.store'));

    $application = array_keys((new HandleInertiaRequests)->share($request));

    (new ShareInertiaProps)->handle($request, fn (): Response => new Response);

    expect(array_diff(array_keys(Inertia::getShared()), $application))->toBe([]);
});
