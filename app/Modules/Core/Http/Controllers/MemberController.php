<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Exceptions\InvalidReportingLine;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\HashId;
use App\Modules\Core\Support\OrganizationContext;
use App\Modules\Core\Support\OrganizationMembers;
use App\Modules\Core\Support\OrganizationRoles;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Where an organization owner reviews everyone who belongs to the organization.
 *
 * A read-only roster: it lists each member's name and the roles they hold, and
 * hands the actual inviting off to {@see MemberInvitationController}. It sits
 * behind its own `members.manage` permission — the plain `members.view` an
 * ordinary member also holds is not enough to see the whole membership.
 */
class MemberController extends Controller
{
    /**
     * Show the organization's members, optionally narrowed by a search term and
     * a role.
     */
    public function index(Request $request): Response
    {
        $organization = OrganizationContext::current();

        Gate::authorize('manageMembers', $organization);

        $search = trim((string) $request->query('search', ''));
        $role = trim((string) $request->query('role', ''));

        // Role filtering and eager loading both read the roles relation, which is
        // team scoped, so the whole query has to run with the team pointed at
        // this organization.
        $members = OrganizationRoles::within($organization, fn (): array => $organization->users()
            ->with('roles')
            ->when($search !== '', fn (Builder $query): Builder => $query->matchingSearch($search))
            ->when($role !== '', fn (Builder $query): Builder => $query->whereHas('roles', fn (Builder $roles): Builder => $roles->where('name', $role)))
            ->orderBy('first_name')
            ->orderBy('last_name')
            // `id` stays in the column list: it is what `hash_id` is derived
            // from, and is dropped from the response by the model.
            ->get(['users.id', 'first_name', 'last_name', 'email'])
            ->map(fn (User $member): array => [
                ...$member->only(['hash_id', 'full_name', 'email']),
                'roles' => $member->roles->pluck('name')->all(),
                'manager' => $member->managerIn($organization)?->hash_id,
            ])
            ->all());

        return Inertia::render('members/index', [
            'members' => $members,

            // Everyone a member could report to, for the manager column.
            'managerOptions' => $organization->users()
                ->orderBy('first_name')
                ->get(['users.id', 'first_name', 'last_name'])
                ->map(fn (User $candidate): array => [
                    'hash_id' => (string) $candidate->hash_id,
                    'name' => $candidate->full_name,
                ])->values()->all(),
            'roles' => $this->filterableRoles($organization),
            'filters' => [
                'search' => $search,
                'role' => $role,
            ],
        ]);
    }

    /**
     * Say who a member reports to here, or that they report to nobody.
     *
     * The write half of the reporting line: the rules — same organization, no
     * self-management, no loops — are enforced where the line is set, and an
     * attempt that breaks one comes back as the validation error it is.
     */
    public function updateManager(Request $request, User $member): RedirectResponse
    {
        $organization = OrganizationContext::current();

        Gate::authorize('manageMembers', $organization);

        abort_unless($member->belongsToOrganization($organization), 404);

        /** @var array{manager: string|null} $validated */
        $validated = $request->validate([
            'manager' => ['nullable', 'string'],
        ]);

        $manager = null;

        if (is_string($validated['manager']) && $validated['manager'] !== '') {
            $key = HashId::decode($validated['manager']);
            $manager = $key === null ? null : $organization->users()->whereKey($key)->first();

            if ($manager === null) {
                throw ValidationException::withMessages(['manager' => __('core::members.manager_unknown')]);
            }
        }

        try {
            OrganizationMembers::setManager($organization, $member, $manager);
        } catch (InvalidReportingLine) {
            throw ValidationException::withMessages(['manager' => __('core::members.manager_invalid')]);
        }

        return back();
    }

    /**
     * The organization's own roles, ownership included, as filter options.
     *
     * @return list<array{value: string, label: string}>
     */
    private function filterableRoles(Organization $organization): array
    {
        return array_values(
            Role::query()
                ->where('guard_name', 'web')
                ->where('organization_id', $organization->getKey())
                ->orderBy('name')
                ->pluck('name')
                ->map(fn (string $name): array => ['value' => $name, 'label' => $name])
                ->all()
        );
    }
}
