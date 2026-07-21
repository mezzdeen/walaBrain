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
     * Both arrive from the form as strings, so both are cast here.
     *
     * The two are treated differently when they are missing, because missing
     * means different things. An unchecked box posts nothing at all, so an
     * absent provider is a provider that was switched off — and the whole map
     * is rebuilt from what the application supports, so an absent one cannot
     * quietly keep its old value and an invented one cannot be introduced.
     * The toggle always posts, through a hidden input; if it did not arrive,
     * the request is malformed, and it is left absent so `required` says so
     * rather than being read as a silent instruction to close the platform.
     */
    protected function prepareForValidation(): void
    {
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

        if ($this->has('registration_open')) {
            $this->merge([
                'registration_open' => filter_var($this->input('registration_open'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
}
