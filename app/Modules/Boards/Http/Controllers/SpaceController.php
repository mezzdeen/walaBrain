<?php

namespace App\Modules\Boards\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Boards\Models\Board;
use App\Modules\Core\Enums\OrganizationPermission;
use App\Modules\Core\Models\Space;
use App\Modules\Core\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The spaces somebody can reach, and the boards inside each.
 *
 * How a person finds their way to a board: the list shows only the spaces
 * membership (or administration) opens to them, which is also everything the
 * boards inside could show them anyway.
 */
class SpaceController extends Controller
{
    /**
     * The reachable spaces, each with its boards.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Space::class);

        /** @var User $user */
        $user = $request->user();

        $administersSpaces = $user->can(OrganizationPermission::ManageSpaces->value);

        $spaces = Space::query()
            ->with('members:id')
            ->orderBy('position')
            ->get()
            ->filter(fn (Space $space): bool => $administersSpaces || $space->accessFor($user) !== null)
            ->values();

        $boards = Board::query()
            ->whereIn('space_id', $spaces->pluck('id'))
            ->orderBy('position')
            ->get()
            ->groupBy('space_id');

        return Inertia::render('spaces/index', [
            'spaces' => $spaces->map(fn (Space $space): array => [
                'hash_id' => $space->hash_id,
                'name' => $space->name,
                'is_default' => $space->is_default,
                'boards' => ($boards[$space->id] ?? collect())->map(fn (Board $board): array => [
                    'hash_id' => $board->hash_id,
                    'name' => $board->name,
                ])->values()->all(),
            ])->all(),
        ]);
    }
}
