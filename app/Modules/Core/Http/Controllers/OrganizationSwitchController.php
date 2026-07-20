<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\OrganizationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrganizationSwitchController extends Controller
{
    /**
     * Switch the signed-in user to another of their organizations.
     */
    public function update(Request $request, Organization $organization): RedirectResponse
    {
        // Asked for by guard rather than through the default one, which the
        // route's `auth` middleware repoints per platform.
        $user = $request->user('web');

        // Not a validation error: asking to act as an organization you are not a
        // member of is a refused request, not a mistyped field.
        abort_unless($user instanceof User && $user->belongsToOrganization($organization), 403);

        OrganizationContext::switch($organization);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('core::organizations.switched', ['name' => $organization->name]),
        ]);

        return back();
    }
}
