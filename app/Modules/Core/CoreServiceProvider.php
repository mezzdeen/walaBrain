<?php

namespace App\Modules\Core;

use App\Modules\Core\Models\Admin;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\User;
use App\Modules\Core\Policies\RolePolicy;
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

        // The Role model comes from the package's config, so it is not covered
        // by Laravel's Model/Policy naming convention.
        Gate::policy(Role::class, RolePolicy::class);

        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->loadTranslationsFrom(__DIR__.'/lang', 'core');
        $this->loadRoutesFrom(__DIR__.'/routes/super.php');
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
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
