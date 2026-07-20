<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Http\Requests\StoreOrganizationRequest;
use App\Modules\Core\Http\Requests\UpdateOrganizationRequest;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Support\OrganizationRoles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    /**
     * Show a listing of the organizations.
     */
    public function index(): Response
    {
        return Inertia::render('super/organizations/index', [
            'organizations' => Organization::query()
                ->withCount('users')
                ->latest()
                ->get(['id', 'name', 'created_at']),
        ]);
    }

    /**
     * Show the form for creating a new organization.
     */
    public function create(): Response
    {
        return Inertia::render('super/organizations/create');
    }

    /**
     * Store a newly created organization.
     */
    public function store(StoreOrganizationRequest $request): RedirectResponse
    {
        // An organization without its roles cannot be administered, so the two
        // either happen together or not at all.
        DB::transaction(function () use ($request): void {
            OrganizationRoles::provision(Organization::create($request->validated()));
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Organization created.')]);

        return to_route('super.organizations.index');
    }

    /**
     * Display the given organization and its members.
     */
    public function show(Organization $organization): Response
    {
        return Inertia::render('super/organizations/show', [
            'organization' => $organization,
            'users' => $organization->users()->get(['users.id', 'name', 'email']),
        ]);
    }

    /**
     * Show the form for editing the given organization.
     */
    public function edit(Organization $organization): Response
    {
        return Inertia::render('super/organizations/edit', [
            'organization' => $organization->only(['id', 'name']),
        ]);
    }

    /**
     * Update the given organization.
     */
    public function update(UpdateOrganizationRequest $request, Organization $organization): RedirectResponse
    {
        $organization->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Organization updated.')]);

        return to_route('super.organizations.index');
    }

    /**
     * Remove the given organization.
     */
    public function destroy(Organization $organization): RedirectResponse
    {
        $organization->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Organization deleted.')]);

        return to_route('super.organizations.index');
    }
}
