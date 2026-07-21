<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * Only what the application itself owns. A module contributes its own props
     * from its own middleware, which runs after this one and so overwrites any
     * key it also provides: `Inertia::share()` merges, last writer winning.
     * The defaults below are what keeps the shell renderable when no module
     * supplies the richer values.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'auth' => [
                'user' => $request->user(),
                'admin' => null,
            ],
            'locale' => app()->getLocale(),
            'direction' => 'ltr',
            'supportedLocales' => [config('app.locale')],
            'translations' => [],
        ];
    }
}
