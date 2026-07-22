<?php

namespace App\Modules\Core\Http\Requests;

use App\Modules\Core\Enums\OrganizationPermission;
use App\Modules\Core\Support\OrganizationContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationRoleRequest extends FormRequest
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
                Rule::unique('roles', 'name')
                    ->where(
                        fn (Builder $query): Builder => $query
                            ->where('guard_name', 'web')
                            ->where('organization_id', $organization?->getKey()),
                    )
                    ->ignore($this->route('role')),
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
