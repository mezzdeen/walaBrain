<?php

namespace App\Modules\Core\Http\Requests;

use App\Modules\Core\Support\PlatformSettings;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePlatformSettingsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'registration_open' => ['required', 'boolean'],

            // The keys are not validated here because they are not taken from
            // the request at all: `prepareForValidation` rebuilds the map from
            // the providers the application supports, so a crafted post cannot
            // add a provider that does not exist or drop one that does.
            'social_providers' => ['array'],
            'social_providers.*' => ['boolean'],
        ];
    }

    /**
     * Normalise the toggle and the checkboxes before they are validated.
     *
     * The toggle always posts, through a hidden input; if it did not arrive,
     * the request is malformed, and it is left absent so `required` says so
     * rather than being read as a silent instruction to close the platform.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('registration_open')) {
            $this->merge([
                'registration_open' => filter_var($this->input('registration_open'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        // Providers are only editable while registration is open: the screen
        // hides that section otherwise and posts nothing for it. So the map is
        // rebuilt from the request only when the toggle is open — an unchecked
        // box posts nothing, so an absent provider there is one switched off,
        // and rebuilding from what the application supports keeps a stale row
        // from re-introducing a dropped provider or an invented one from
        // sneaking in. When the platform is being closed, `social_providers` is
        // left off the validated data entirely, so `PlatformSettings::update`
        // keeps the stored providers as they were instead of reading a hidden,
        // unsubmitted section as an instruction to switch them all off.
        if (! $this->boolean('registration_open')) {
            return;
        }

        $submitted = $this->input('social_providers');
        $submitted = is_array($submitted) ? $submitted : [];

        $providers = [];

        foreach (array_keys(PlatformSettings::socialProviders()) as $provider) {
            $providers[$provider] = filter_var(
                $submitted[$provider] ?? false,
                FILTER_VALIDATE_BOOLEAN,
            );
        }

        $this->merge(['social_providers' => $providers]);
    }
}
