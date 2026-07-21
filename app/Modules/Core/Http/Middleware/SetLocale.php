<?php

namespace App\Modules\Core\Http\Middleware;

use App\Modules\Core\Models\Admin;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\Locale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Resolve the active locale for the request and apply it.
     *
     * The direction and translation version are shared with the root template
     * so the first paint already has the correct `dir`, avoiding a flash of
     * left-to-right layout on right-to-left locales.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolve($request);

        app()->setLocale($locale);

        View::share('direction', Locale::direction($locale));

        return $next($request);
    }

    /**
     * Resolve the locale from the cookie, the signed-in user, then the browser.
     */
    protected function resolve(Request $request): string
    {
        $cookie = $request->cookie('locale');

        if (is_string($cookie) && Locale::isSupported($cookie)) {
            $this->remember($request, $cookie);

            return $cookie;
        }

        $preference = $this->identity($request)?->locale;

        if (Locale::isSupported($preference)) {
            return $preference;
        }

        $preferred = $request->getPreferredLanguage(Locale::SUPPORTED);

        return Locale::isSupported($preferred) ? $preferred : Locale::default();
    }

    /**
     * Persist the choice on the signed-in identity so it follows them to other
     * devices. The cookie only covers the browser it was set in.
     */
    protected function remember(Request $request, string $locale): void
    {
        $identity = $this->identity($request);

        if ($identity === null || $identity->locale === $locale) {
            return;
        }

        $identity->forceFill(['locale' => $locale])->save();
    }

    /**
     * The signed-in identity, whichever platform is being served.
     *
     * Both guards are asked by name rather than through the default guard,
     * which the route's `auth:` middleware repoints at the platform it
     * protects. Relying on it here would hand the admin platform's `Admin` to
     * code that had only ever been given a `User`.
     */
    protected function identity(Request $request): User|Admin|null
    {
        $identity = $request->user('web') ?? $request->user('super');

        return $identity instanceof User || $identity instanceof Admin
            ? $identity
            : null;
    }
}
