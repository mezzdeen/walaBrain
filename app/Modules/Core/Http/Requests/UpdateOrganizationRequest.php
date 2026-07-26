<?php

namespace App\Modules\Core\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class UpdateOrganizationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],

            // Null is a real answer, not a missing one: it means the
            // organization wants the application's own colours rather than a
            // brand of its own. Only the company screen submits this — the
            // platform's edit form leaves it out, and an absent key never
            // reaches `validated()`, so it cannot wipe a saved colour.
            'color' => ['nullable', 'string', 'hex_color'],
        ];
    }

    /**
     * Normalise the colour before it is validated.
     *
     * A colour input posts an empty string when cleared, which is the user
     * asking for no brand colour rather than for the empty one.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('color')) {
            $color = Str::lower(trim((string) $this->input('color')));

            $this->merge(['color' => $color === '' ? null : $color]);
        }
    }
}
