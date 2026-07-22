<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\OrganizationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MemberSearchController extends Controller
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
     * Suggest existing accounts as an address is typed on the invite form.
     *
     * Answers the member field the same way {@see UserSearchController} answers
     * the owner one, but gated on the organization's own invite permission and
     * confined to it: people already in the organization are dropped, since
     * there is nothing to invite them to.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $organization = OrganizationContext::current();

        Gate::authorize('inviteMembers', $organization);

        $query = trim((string) $request->query('q', ''));

        if (mb_strlen($query) < self::MINIMUM_QUERY_LENGTH) {
            return response()->json(['users' => []]);
        }

        $escaped = addcslashes($query, '%_\\');

        // Members of the organization are not filtered out: hiding an existing
        // account makes it look like no account exists, which is the opposite of
        // helpful. They are suggested like anyone else, and the invite form's
        // own validation is what refuses to invite someone already inside.
        $users = User::query()
            ->where(function ($builder) use ($escaped): void {
                // Addresses are matched from the start: the user is typing one
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
            // `id` stays in the column list: it is what `hash_id` is derived
            // from. The integer itself is hidden from the response.
            ->get(['id', 'first_name', 'last_name', 'email']);

        // Which of the matches already belong to the organization, resolved in a
        // single query so the field can mark them and refuse to pick them. The
        // invite form validates the same thing again — this only saves a
        // round-trip to be told what the list could already show.
        $members = $organization->users()
            ->whereIn('users.id', $users->modelKeys())
            ->pluck('users.id')
            ->flip();

        return response()->json([
            'users' => $users->map(fn (User $user): array => [
                'hash_id' => $user->hash_id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'already_member' => $members->has($user->getKey()),
            ])->all(),
        ]);
    }
}
