<?php

namespace App\Modules\Core;

use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the Core module's own routes, migrations and translations.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->loadTranslationsFrom(__DIR__.'/lang', 'core');
        $this->loadRoutesFrom(__DIR__.'/routes/super.php');
    }
}
