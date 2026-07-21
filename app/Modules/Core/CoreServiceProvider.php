<?php

namespace App\Modules\Core;

use App\Modules\Core\Actions\Fortify\CreateNewUser;
use App\Modules\Core\Actions\Fortify\ResetUserPassword;
use App\Modules\Core\Http\Middleware\EnsureRegistrationIsOpen;
use App\Modules\Core\Http\Middleware\RequiresOrganization;
use App\Modules\Core\Http\Middleware\SetLocale;
use App\Modules\Core\Http\Middleware\SetOrganizationContext;
use App\Modules\Core\Http\Middleware\ShareInertiaProps;
use App\Modules\Core\Listeners\ProvisionOrganizationForNewUser;
use App\Modules\Core\Models\Admin;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\PlatformSetting;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\User;
use App\Modules\Core\Policies\OrganizationPolicy;
use App\Modules\Core\Policies\PlatformSettingPolicy;
use App\Modules\Core\Policies\RolePolicy;
use App\Modules\Core\Policies\UserPolicy;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Http\Kernel as KernelContract;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the Core module's own routes, migrations and translations.
     */
    public function boot(): void
    {
        $this->registerMorphMap();
        $this->registerPolicies();
        $this->registerMiddleware();
        $this->registerListeners();
        $this->registerFortifyActions();

        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->loadTranslationsFrom(__DIR__.'/lang', 'core');
        $this->loadRoutesFrom(__DIR__.'/routes/super.php');
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
    }

    /**
     * Map every model the module authorizes against to its policy, and let the
     * super admin role through every gate.
     *
     * Registered explicitly rather than left to Laravel's naming convention:
     * `Role` comes from the permission package's config and so falls outside it
     * anyway, and listing all three keeps the map readable in one place instead
     * of splitting it across two mechanisms. The bypass belongs here too: the
     * role it recognises is the module's, and nothing outside knows about it.
     */
    private function registerPolicies(): void
    {
        Gate::policy(Organization::class, OrganizationPolicy::class);
        Gate::policy(PlatformSetting::class, PlatformSettingPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        Gate::before(
            // Null rather than false when the bypass does not apply: false would
            // deny the ability outright instead of letting the normal checks run.
            fn (mixed $user): ?bool => $user instanceof Admin && $user->isSuperAdmin() ? true : null,
        );
    }

    /**
     * Tell Fortify how this module's user is created and how its password is
     * reset. Fortify itself is the application's, but the model these act on is
     * the module's, so the actions are registered from here.
     */
    private function registerFortifyActions(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
    }

    /**
     * Register the middleware the module contributes, both the aliases its own
     * routes rely on and the two it adds to every web request.
     *
     * Declared here rather than in `bootstrap/app.php` so the module carries
     * everything it needs, the same way it carries its routes and policies.
     */
    private function registerMiddleware(): void
    {
        $router = $this->app->make(Router::class);

        $router->aliasMiddleware('organization', RequiresOrganization::class);
        $router->aliasMiddleware('registration', EnsureRegistrationIsOpen::class);

        // Resolved by its contract, which is what the container binds, but typed
        // as the concrete kernel: the group and priority methods live there.
        /** @var Kernel $kernel */
        $kernel = $this->app->make(KernelContract::class);

        $kernel->appendMiddlewareToGroup('web', SetLocale::class);
        $kernel->appendMiddlewareToGroup('web', SetOrganizationContext::class);
        // After `SetLocale`, which is what the language props are read from.
        $kernel->appendMiddlewareToGroup('web', ShareInertiaProps::class);

        // Permission checks are meaningless until the active organization is
        // known, and route model binding may scope a lookup by it, so the
        // context has to be in place before bindings are substituted. Without
        // this a request that should be forbidden 404s instead. Appending puts
        // it after `SubstituteBindings` in the group; the priority list is what
        // pulls it back in front.
        $kernel->addToMiddlewarePriorityBefore(
            SubstituteBindings::class,
            SetOrganizationContext::class,
        );
    }

    /**
     * Register what the module does in response to framework events.
     *
     * Declared here for the same reason the middleware is: the module carries
     * everything it needs to work when it is registered, and nothing about it
     * has to be remembered elsewhere.
     */
    private function registerListeners(): void
    {
        Event::listen(Verified::class, ProvisionOrganizationForNewUser::class);
    }

    /**
     * Keep polymorphic tables free of fully qualified class names, so the module
     * can be renamed or moved without a data migration. Enforced rather than
     * merely mapped, so a model that is missing from the map fails loudly the
     * first time it is used polymorphically instead of writing a class name.
     */
    private function registerMorphMap(): void
    {
        Relation::enforceMorphMap([
            'user' => User::class,
            'admin' => Admin::class,
            'organization' => Organization::class,
        ]);
    }
}
