<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
                    ->orWhere('name', 'like', '%'.$escaped.'%');
            })
            ->orderBy('email')
            ->limit(self::RESULT_LIMIT)
            ->get(['id', 'name', 'email']);

        return response()->json(['users' => $users]);
    }
}
