<?php

namespace App\Modules\Core\Http\Middleware;

use App\Modules\Core\Enums\SuperPermission;
use App\Modules\Core\Models\Admin;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\BrandColor;
use App\Modules\Core\Support\Locale;
use App\Modules\Core\Support\OrganizationContext;
use App\Modules\Core\Support\Platform;
use App\Modules\Core\Support\PlatformSettings;
use App\Modules\Core\Support\Translations;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Contracts\Permission as PermissionContract;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds the module's own props to every Inertia page.
 *
 * Middleware rather than an `Inertia::share()` call in the service provider,
 * because two of these props have to be read after `SetLocale` has run and a
 * provider boots long before any middleware does. Registered after `SetLocale`
 * in the web group, so `app()->getLocale()` here is the visitor's language and
 * not the configured default.
 *
 * Sharing again overwrites: whatever the application shared under these keys is
 * replaced by the values below, which is what lets the application ship neutral
 * defaults for a request this module never reaches.
 */
class ShareInertiaProps
{
    public function handle(Request $request, Closure $next): Response
    {
        Inertia::share($this->props($request));

        return $next($request);
    }

    /**
     * @return array<string, mixed>
     */
    protected function props(Request $request): array
    {
        return [
            // Both guards named rather than left to the default, which
            // `auth:super` repoints for the rest of the request: `$request->user()`
            // on the admin platform would hand an `Admin` to a prop the front end
            // types as a `User`. The admin platform has no company user at all,
            // so it is told so rather than shown whichever one shares the browser.
            'auth' => [
                'user' => Platform::isAdmin($request) ? null : $request->user('web'),
                'admin' => $request->user('super'),
            ],
            // Closures, not values: this runs before the route middleware, so at
            // this point neither `auth:super` nor the organization context has
            // run yet. Inertia resolves closures at render time, by which point
            // both have.
            'permissions' => fn (): array => $this->permissionsFor($request),
            'organization' => fn (): ?array => Platform::isAdmin($request)
                ? null
                : OrganizationContext::current()?->only(['hash_id', 'name']),
            'organizations' => fn (): array => $this->switchableOrganizations($request),
            // The finished stylesheet, not the colour: the Blade root view has
            // already written this exact text for the first paint, and sending
            // it rather than rebuilding it on the client is what keeps the two
            // from ever disagreeing. An Inertia visit never re-renders the root
            // view, so without this the colour would be stale until a reload.
            'brandColorCss' => fn (): string => Platform::isAdmin($request)
                ? ''
                : BrandColor::css(OrganizationContext::current()?->color),
            // What the sign-in screen needs to know to offer a way to sign up,
            // and nothing more. Admin platform excluded: an admin account is
            // never self-created, so the question does not arise there.
            'registration' => fn (): ?array => Platform::isAdmin($request)
                ? null
                : [
                    'open' => PlatformSettings::registrationIsOpen(),
                    'providers' => array_keys(array_filter(PlatformSettings::socialProviders())),
                ],
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

    /**
     * The permission names held by whoever is signed in, for hiding interface
     * elements the request would be refused anyway.
     *
     * Deliberately not an `Inertia::once()` prop: unlike the translations these
     * change whenever the user switches organization.
     *
     * @return list<string>
     */
    protected function permissionsFor(Request $request): array
    {
        $identity = Platform::isAdmin($request)
            ? $request->user('super')
            : $request->user('web');

        if ($identity === null) {
            return [];
        }

        if ($identity instanceof Admin && $identity->isSuperAdmin()) {
            // The gate lets this role through everything, so the interface has
            // to be told the same rather than reading the role's stored rows.
            return SuperPermission::values();
        }

        // Re-indexed so this always encodes as a JSON array; a collection with
        // gaps in its keys would reach the front end as an object.
        return array_values($identity->getAllPermissions()
            ->map(fn (PermissionContract $permission): string => $permission->name)
            ->all());
    }

    /**
     * The organizations the signed-in user may switch between.
     *
     * Only the public code and the name: mapped rather than serialized whole,
     * so the pivot rows the relation carries do not ride along into the page
     * props.
     *
     * @return list<array{hash_id: string, name: string}>
     */
    protected function switchableOrganizations(Request $request): array
    {
        if (Platform::isAdmin($request)) {
            return [];
        }

        $user = $request->user('web');

        if (! $user instanceof User) {
            return [];
        }

        // `organizations.id` stays in the column list: it is what `hash_id` is
        // derived from, and a row loaded without it has no address to link to.
        return array_values($user->organizations()
            ->orderBy('name')
            ->get(['organizations.id', 'organizations.name'])
            ->map(fn (Organization $organization): array => [
                'hash_id' => $organization->hash_id,
                'name' => $organization->name,
            ])
            ->all());
    }
}
