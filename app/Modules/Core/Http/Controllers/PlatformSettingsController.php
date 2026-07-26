<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Http\Requests\UpdatePlatformSettingsRequest;
use App\Modules\Core\Models\PlatformSetting;
use App\Modules\Core\Support\PlatformSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The platform's own settings, administered from the super platform.
 *
 * Distinct from {@see OrganizationSettingsController}, which is one tenant
 * editing itself. What is settled here applies before any tenant exists — most
 * of all whether an account can be created without an invitation at all.
 *
 * Authorized against the model class rather than an instance: the settings are
 * a table of switches, not a record someone owns, so there is nothing to pass
 * the gate but the subject itself.
 */
class PlatformSettingsController extends Controller
{
    /**
     * Show the platform settings.
     */
    public function edit(): Response
    {
        Gate::authorize('update', PlatformSetting::class);

        return Inertia::render('super/settings', [
            'settings' => [
                'registrationOpen' => PlatformSettings::registrationIsOpen(),
                'socialProviders' => PlatformSettings::socialProviders(),
            ],
        ]);
    }

    /**
     * Save the platform settings.
     */
    public function update(UpdatePlatformSettingsRequest $request): RedirectResponse
    {
        Gate::authorize('update', PlatformSetting::class);

        PlatformSettings::update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('core::platform.updated')]);

        return to_route('super.settings.edit');
    }
}
