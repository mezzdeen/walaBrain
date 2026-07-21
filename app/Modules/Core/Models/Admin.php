<?php

namespace App\Modules\Core\Models;

use App\Modules\Core\Concerns\HasHashId;
use App\Modules\Core\Database\Factories\AdminFactory;
use App\Modules\Core\Enums\SuperRole;
use App\Modules\Core\Support\PermissionTeam;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $locale
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Role> $roles
 * @property-read Collection<int, Permission> $permissions
 */
#[Fillable(['name', 'email', 'password', 'locale'])]
#[Hidden(['password', 'remember_token'])]
class Admin extends Authenticatable
{
    /**
     * Roles resolve to the `super` guard automatically: spatie reverse-maps the
     * model through `config('auth.guards')`, and the `admins` provider names
     * this class. Assignments live on the {@see PermissionTeam::SUPER} sentinel
     * team rather than an organization.
     *
     * @use HasFactory<AdminFactory>
     */
    use HasFactory, HasHashId, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Whether the admin holds the role that bypasses every permission check.
     *
     * Correct regardless of the team the caller is pointed at, so it also holds
     * in console commands and queued jobs where no middleware set a context. The
     * common path — a request already on the super team — is a plain role check
     * against the relation the request has loaded anyway.
     */
    public function isSuperAdmin(): bool
    {
        if (getPermissionsTeamId() === PermissionTeam::SUPER) {
            return $this->hasRole(SuperRole::SuperAdmin->value);
        }

        // Queried rather than read through the relation, which would otherwise
        // be cached against the wrong team for whoever loaded it.
        return PermissionTeam::on(
            PermissionTeam::SUPER,
            fn (): bool => $this->roles()->where('name', SuperRole::SuperAdmin->value)->exists(),
        );
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return AdminFactory::new();
    }
}
