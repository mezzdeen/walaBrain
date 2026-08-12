<?php

namespace App\Modules\Flows;

use App\Modules\Core\Models\User;
use App\Modules\Core\Support\MyWorkSources;
use App\Modules\Flows\Listeners\StartRunForSubmission;
use App\Modules\Flows\Models\Approval;
use App\Modules\Flows\Models\Flow;
use App\Modules\Flows\Models\FlowStep;
use App\Modules\Flows\Models\Run;
use App\Modules\Flows\Policies\ApprovalPolicy;
use App\Modules\Forms\Events\FormSubmitted;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * The automation behind a submission: a fixed sequence of approvals and tasks,
 * executed as a durable run per node.
 *
 * Depends on Forms (whose submissions it listens for) and Boards (whose nodes
 * it moves). Nothing depends on it: delete this directory and submissions
 * still create referenced nodes — they just stop setting anything in motion.
 */
class FlowsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the module's migrations, translations, routes, policies and
     * contributions to the rest of the platform.
     */
    public function boot(): void
    {
        Relation::enforceMorphMap([
            'flow' => Flow::class,
            'flow_step' => FlowStep::class,
            'run' => Run::class,
            'approval' => Approval::class,
        ]);

        Gate::policy(Approval::class, ApprovalPolicy::class);

        Event::listen(FormSubmitted::class, StartRunForSubmission::class);

        // What this module has waiting on a person: their pending decisions.
        // Registered rather than queried from the My Work screen directly, so
        // the screen needs no knowledge of this module to show its items.
        MyWorkSources::register('approvals', fn (User $user): array => Approval::query()
            ->pendingFor($user)
            ->with(['node:id,title,reference,created_at'])
            ->get()
            ->map(fn (Approval $approval): array => [
                'hash_id' => $approval->hash_id,
                'reference' => $approval->node->reference,
                'title' => $approval->node->title,
                'requested_at' => $approval->created_at?->toDateString(),
            ])->all());

        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->loadTranslationsFrom(__DIR__.'/lang', 'flows');
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
    }
}
