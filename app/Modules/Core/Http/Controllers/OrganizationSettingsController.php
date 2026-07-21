<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Http\Requests\UpdateOrganizationRequest;
use App\Modules\Core\Support\OrganizationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Lets an organization edit its own details from the company platform.
 *
 * Deliberately not one of the `/settings` screens: those are the signed-in
 * user's own account and share a sub-navigation — profile, security,
 * appearance — that would read as though the organization were a personal
 * preference. This is the organization itself, so it sits at the top level
 * beside the dashboard.
 *
 * Both actions act on the organization the request is already switched to,
 * which the middleware resolved from the session — there is no organization
 * parameter to pass, and so no way to aim the form at someone else's tenant.
 *
 * The platform's own version of this screen is {@see OrganizationController},
 * which administers any organization and answers to the super guard instead.
 */
class OrganizationSettingsController extends Controller
{
    /**
     * Show the current organization's details.
     *
     * Authorized by `update` rather than `view`: an ordinary member holds
     * `organization.view` and would otherwise reach an edit form whose save
     * button is refused. The gate to open the screen is the gate to submit it.
     */
    public function edit(): Response
    {
        $organization = OrganizationContext::current();

        Gate::authorize('update', $organization);

        return Inertia::render('organization', [
            'organization' => $organization->only(['id', 'name', 'color']),
        ]);
    }

    /**
     * Update the current organization's details.
     */
    public function update(UpdateOrganizationRequest $request): RedirectResponse
    {
        $organization = OrganizationContext::current();

        Gate::authorize('update', $organization);

        $organization->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('core::organizations.updated')]);

        return to_route('organization.edit');
    }
}
