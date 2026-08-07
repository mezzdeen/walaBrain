<?php

namespace App\Modules\Boards;

use App\Modules\Boards\Models\Board;
use App\Modules\Boards\Models\Field;
use App\Modules\Boards\Models\Group;
use App\Modules\Boards\Models\Node;
use App\Modules\Boards\Policies\BoardPolicy;
use App\Modules\Boards\Policies\NodePolicy;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Where work lives: boards, the groups their nodes are partitioned into, the
 * fields a board's nodes carry, and the nodes themselves.
 *
 * Depends on Core for the organization a board belongs to and the space it
 * lives in. Nothing in Core depends on this, which is what lets the module be
 * removed by deleting its directory.
 */
class BoardsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the module's migrations, translations and policies.
     */
    public function boot(): void
    {
        $this->registerMorphMap();
        $this->registerPolicies();

        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->loadTranslationsFrom(__DIR__.'/lang', 'boards');
    }

    /**
     * Name this module's models to the polymorphic tables that store them.
     *
     * Merged into whatever is already mapped rather than replacing it, so Core's
     * entries survive and neither module has to know the other's. A node is
     * addressed polymorphically the moment it records anything on its activity
     * timeline, and the map is enforced, so a missing entry fails loudly the
     * first time instead of writing a class name into the database.
     */
    private function registerMorphMap(): void
    {
        Relation::enforceMorphMap([
            'board' => Board::class,
            'group' => Group::class,
            'field' => Field::class,
            'node' => Node::class,
        ]);
    }

    /**
     * Map the module's models to their policies.
     */
    private function registerPolicies(): void
    {
        Gate::policy(Board::class, BoardPolicy::class);
        Gate::policy(Node::class, NodePolicy::class);
    }
}
