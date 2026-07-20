<?php

namespace App\Http\Middleware;

use App\Modules\Core\Support\Locale;
use App\Modules\Core\Support\Translations;
use Illuminate\Http\Request;
use Inertia\Inertia;
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
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'admin' => $request->user('super'),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'locale' => app()->getLocale(),
            'direction' => Locale::direction(app()->getLocale()),
            'supportedLocales' => Locale::SUPPORTED,
            // Sent on the initial load only, never on subsequent visits. The
            // key changes with the locale and the translation version, so a
            // language switch or an edited lang file re-sends the dictionary.
            'translations' => Inertia::once(
                fn (): array => Translations::for(app()->getLocale()),
            )->as('translations.'.app()->getLocale().'.'.Locale::version()),
        ];
    }
}
