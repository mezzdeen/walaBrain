<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Support\OrganizationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class OrganizationSwitchController extends Controller
{
    /**
     * Switch the signed-in user to another of their organizations.
     */
    public function update(Organization $organization): RedirectResponse
    {
        // Not a validation error: asking to act as an organization you are not a
        // member of is a refused request, not a mistyped field.
        Gate::authorize('switchTo', $organization);

        OrganizationContext::switch($organization);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('core::organizations.switched', ['name' => $organization->name]),
        ]);

        // Not `back()`: the page the switch was made from may address a record
        // of the organization just left, which after the switch is at best a
        // 404 and at worst another organization's data on screen. The dashboard
        // is the one page guaranteed to mean something in every organization.
        return to_route('dashboard');
    }
}
