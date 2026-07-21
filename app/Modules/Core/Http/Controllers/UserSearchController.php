<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserSearchController extends Controller
{
    /**
     * The shortest query worth running.
     *
     * A single character matches most of the table, which is a slow query and a
     * directory dump rather than a search.
     */
    private const MINIMUM_QUERY_LENGTH = 2;

    /**
     * How many matches the typeahead shows at once.
     */
    private const RESULT_LIMIT = 8;

    /**
     * Find accounts matching a partial name or address.
     *
     * Answers the organization create form's owner field, which needs to know
     * whether an address already belongs to someone before the form is
     * submitted. Gated on `organizations.create` so it cannot be used as a
     * general directory.
     */
    public function __invoke(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', User::class);

        $query = trim((string) $request->query('q', ''));

        if (mb_strlen($query) < self::MINIMUM_QUERY_LENGTH) {
            return response()->json(['users' => []]);
        }

        $escaped = addcslashes($query, '%_\\');

        $users = User::query()
            ->where(function ($builder) use ($escaped): void {
                // Addresses are matched from the start: the admin is typing one
                // out, not searching for a fragment of it.
                $builder->where('email', 'like', $escaped.'%')
                    ->orWhere('first_name', 'like', '%'.$escaped.'%')
                    ->orWhere('last_name', 'like', '%'.$escaped.'%');

                // A query with a space in it reads as a whole name, which
                // neither column matches on its own: "Ada Lov" is a first name
                // followed by the start of a surname.
                if (str_contains($escaped, ' ')) {
                    [$first, $last] = explode(' ', $escaped, 2);

                    $builder->orWhere(function ($nested) use ($first, $last): void {
                        $nested->where('first_name', 'like', $first.'%')
                            ->where('last_name', 'like', $last.'%');
                    });
                }
            })
            ->orderBy('email')
            ->limit(self::RESULT_LIMIT)
            ->get(['id', 'first_name', 'last_name', 'email']);

        return response()->json(['users' => $users]);
    }
}
