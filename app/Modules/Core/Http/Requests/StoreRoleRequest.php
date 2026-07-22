<?php

namespace App\Modules\Core\Http\Requests;

use App\Modules\Core\Enums\SuperPermission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
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
                // Scoped by hand rather than by a plain unique rule: the table
                // also holds every organization's roles, and PostgreSQL treats
                // nulls as distinct so the column's own unique index would let a
                // second global role with the same name through.
                Rule::unique('roles', 'name')->where(
                    fn (Builder $query): Builder => $query
                        ->where('guard_name', 'super')
                        ->whereNull('organization_id'),
                ),
            ],
            'permissions' => ['array'],
            'permissions.*' => $this->permissionRules(SuperPermission::values()),
        ];
    }

    protected function permissionGuard(): string
    {
        return 'super';
    }
}
