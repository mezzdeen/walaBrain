<?php

namespace App\Modules\Core;

use App\Modules\Core\Models\Admin;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\User;
use App\Modules\Core\Policies\OrganizationPolicy;
use App\Modules\Core\Policies\RolePolicy;
use App\Modules\Core\Policies\UserPolicy;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the Core module's own routes, migrations and translations.
     */
    public function boot(): void
    {
        $this->registerMorphMap();
        $this->registerPolicies();

        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->loadTranslationsFrom(__DIR__.'/lang', 'core');
        $this->loadRoutesFrom(__DIR__.'/routes/super.php');
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
    }

    /**
     * Map every model the module authorizes against to its policy.
     *
     * Registered explicitly rather than left to Laravel's naming convention:
     * `Role` comes from the permission package's config and so falls outside it
     * anyway, and listing all three keeps the map readable in one place instead
     * of splitting it across two mechanisms.
     */
    private function registerPolicies(): void
    {
        Gate::policy(Organization::class, OrganizationPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
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
