<?php

namespace App\Modules\Core\Http\Requests;

use App\Modules\Core\Enums\SuperPermission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    use ResolvesPermissions;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')
                    ->where(
                        fn (Builder $query): Builder => $query
                            ->where('guard_name', 'super')
                            ->whereNull('organization_id'),
                    )
                    ->ignore($this->route('role')),
            ],
            'permissions' => ['array'],
            'permissions.*' => [Rule::in(SuperPermission::values())],
        ];
    }

    protected function permissionGuard(): string
    {
        return 'super';
    }
}
