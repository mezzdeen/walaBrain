<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Http\Requests\StoreOrganizationRequest;
use App\Modules\Core\Http\Requests\UpdateOrganizationRequest;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Services\OrganizationService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function __construct(private readonly OrganizationService $organizations)
    {
        $this->authorizeResource(Organization::class);
    }

    /**
     * Show a listing of the organizations.
     */
    public function index(): Response
    {
        return Inertia::render('super/organizations/index', [
            'organizations' => $this->organizations->listing(),
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
     * Store a newly created organization and settle its ownership.
     */
    public function store(StoreOrganizationRequest $request): RedirectResponse
    {
        $email = $request->validated('owner_email');

        $result = $this->organizations->create(
            $request->validated('name'),
            $email,
            $request->user('super'),
            $request->validated('locale'),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $result->ownerWasAttached()
                ? __('core::organizations.created_with_owner', ['email' => $email])
                : __('core::organizations.created_with_invitation', ['email' => $email]),
        ]);

        return to_route('super.organizations.index');
    }

    /**
     * Display the given organization and its members.
     */
    public function show(Organization $organization): Response
    {
        return Inertia::render('super/organizations/show', [
            'organization' => $organization,
            'users' => $this->organizations->members($organization),
        ]);
    }

    /**
     * Show the form for editing the given organization.
     */
    public function edit(Organization $organization): Response
    {
        return Inertia::render('super/organizations/edit', [
            'organization' => $organization->only(['hash_id', 'name']),
        ]);
    }

    /**
     * Update the given organization.
     */
    public function update(UpdateOrganizationRequest $request, Organization $organization): RedirectResponse
    {
        $this->organizations->update($organization, $request->validated('name'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('core::organizations.updated')]);

        return to_route('super.organizations.index');
    }

    /**
     * Remove the given organization.
     */
    public function destroy(Organization $organization): RedirectResponse
    {
        $this->organizations->delete($organization);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('core::organizations.deleted')]);

        return to_route('super.organizations.index');
    }
}
