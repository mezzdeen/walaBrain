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
use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\PlatformSetting;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\Space;
use App\Modules\Core\Models\User;
use App\Modules\Core\Policies\OrganizationPolicy;
use App\Modules\Core\Policies\PlatformSettingPolicy;
use App\Modules\Core\Policies\RolePolicy;
use App\Modules\Core\Policies\SpacePolicy;
use App\Modules\Core\Policies\UserPolicy;
use App\Modules\Core\Support\OrganizationContext;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Http\Kernel as KernelContract;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * Name this module's models to the packages that have to instantiate them.
     *
     * Through the config repository from here rather than written into
     * `config/auth.php` and `config/permission.php`, so the application's own
     * configuration never names a class that belongs to a module — the same
     * reason its routes no longer name a middleware alias this module
     * registers. Every one of these is read lazily, on first use, long after
     * this has run.
     *
     * Only where nothing is configured already, so an environment that names
     * its own model still wins.
     */
    public function register(): void
    {
        config([
            'auth.guards.super' => [
                'driver' => 'session',
                'provider' => 'admins',
            ],
            'auth.providers.admins' => [
                'driver' => 'eloquent',
                'model' => Admin::class,
            ],
            'auth.providers.users.model' => config('auth.providers.users.model') ?? User::class,
            'permission.models.permission' => config('permission.models.permission') ?? Permission::class,
            'permission.models.role' => config('permission.models.role') ?? Role::class,
            'permission.models.team' => config('permission.models.team') ?? Organization::class,
        ]);
    }

    /**
     * Bootstrap the Core module's own routes, migrations and translations.
     */
    public function boot(): void
    {
        $this->registerMorphMap();
        $this->registerPolicies();
        $this->registerMiddleware();
        $this->registerQueueTenancy();
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
        Gate::policy(Space::class, SpacePolicy::class);
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
     * routes rely on and the three it adds to every web request.
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
     * Carry the organization a job was dispatched from into the worker that
     * runs it.
     *
     * Middleware sets the active organization for web requests, and a queued
     * job runs long after the request that dispatched it has gone. Without
     * this, every job touching a tenant-owned record either reads nothing or
     * throws, and the only remedy would be for each one to remember to name its
     * organization — which is a thing to forget, silently, in the one place
     * nobody watches.
     *
     * Done globally rather than through a base class or a trait so a job cannot
     * opt out by accident. Most of the platform's real work happens on a queue:
     * a flow run sleeping for a fortnight, a reminder, an escalation, a hold
     * expiring.
     */
    private function registerQueueTenancy(): void
    {
        // Stamped at dispatch, when the organization is still known.
        Queue::createPayloadUsing(fn (): array => [
            'organizationId' => OrganizationContext::current()?->getKey(),
        ]);

        Queue::before(function (JobProcessing $event): void {
            $organizationId = $event->job->payload()['organizationId'] ?? null;

            if (! is_int($organizationId)) {
                // Dispatched by something that belongs to no tenant — a console
                // command, a scheduled sweep. It stays unscoped, and anything
                // inside it that needs an organization has to name one.
                OrganizationContext::useGlobal();

                return;
            }

            // A null here means the organization was deleted between dispatch
            // and processing. Deliberately not treated as "act across every
            // tenant": the job is confined to nothing and its scoped writes
            // throw, which is the safe reading of a tenant that no longer
            // exists.
            OrganizationContext::use(Organization::query()->find($organizationId));
        });

        // A worker serves one job after another in a single long-lived process,
        // so anything left set is inherited by whatever runs next — the leak
        // this whole arrangement exists to prevent. Cleared after a job
        // finishes and after one fails, since a failure leaves it set just the
        // same.
        $forget = function (): void {
            OrganizationContext::clear();
            setPermissionsTeamId(null);
        };

        Queue::after($forget);
        Queue::exceptionOccurred($forget);
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
            'space' => Space::class,
        ]);
    }
}
