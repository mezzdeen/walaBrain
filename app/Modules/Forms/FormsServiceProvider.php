<?php

namespace App\Modules\Forms;

use App\Modules\Forms\Models\Form;
use App\Modules\Forms\Policies\FormPolicy;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * The front door of a process: a form collects values into a board's fields,
 * and submitting one creates a node carrying them and a reference number.
 *
 * Depends on Boards for the board a form maps to and the node a submission
 * creates. Knows nothing about Flows: a submission is announced as an event,
 * and whether anything reacts is the listener's business.
 */
class FormsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the module's migrations, translations, routes and policies.
     */
    public function boot(): void
    {
        Relation::enforceMorphMap(['form' => Form::class]);

        Gate::policy(Form::class, FormPolicy::class);

        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->loadTranslationsFrom(__DIR__.'/lang', 'forms');
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
    }
}
