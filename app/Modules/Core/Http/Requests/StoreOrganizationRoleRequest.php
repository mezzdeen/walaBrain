<?php

namespace App\Modules\Core\Http\Requests;

use App\Modules\Core\Enums\OrganizationPermission;
use App\Modules\Core\Support\OrganizationContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrganizationRoleRequest extends FormRequest
{
    use ResolvesPermissions;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $organization = OrganizationContext::current();

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                // Unique within this organization only — two organizations may
                // each have a role of the same name.
                Rule::unique('roles', 'name')->where(
                    fn (Builder $query): Builder => $query
                        ->where('guard_name', 'web')
                        ->where('organization_id', $organization?->getKey()),
                ),
            ],
            'permissions' => ['array'],
            'permissions.*' => $this->permissionRules(OrganizationPermission::values()),
        ];
    }

    protected function permissionGuard(): string
    {
        return 'web';
    }
}
