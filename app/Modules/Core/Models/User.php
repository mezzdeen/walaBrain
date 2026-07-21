<?php

namespace App\Modules\Core\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Modules\Core\Database\Factories\UserFactory;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property-read string $full_name
 * @property string $email
 * @property string|null $locale
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Role> $roles
 * @property-read Collection<int, Permission> $permissions
 * @property-read Collection<int, Organization> $organizations
 */
#[Appends(['full_name'])]
#[Fillable(['first_name', 'last_name', 'email', 'password', 'locale'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements HasLocalePreference, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

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
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * The user's full name.
     *
     * Appended to the model's array form because the interface addresses people
     * by their whole name almost everywhere it addresses them at all.
     *
     * @return Attribute<string, never>
     */
    protected function fullName(): Attribute
    {
        return Attribute::get(fn (mixed $value, array $attributes): string => trim(
            ($attributes['first_name'] ?? '').' '.($attributes['last_name'] ?? '')
        ));
    }

    /**
     * The organizations the user is a member of.
     *
     * @return BelongsToMany<Organization, $this>
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)->withTimestamps();
    }

    /**
     * The locale queued notifications should be rendered in.
     *
     * Returning null leaves Laravel on the application locale, which is the
     * right fallback for a user who has never chosen one.
     */
    public function preferredLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * Determine whether the user is a member of the given organization.
     */
    public function belongsToOrganization(Organization|int $organization): bool
    {
        $key = $organization instanceof Organization ? $organization->getKey() : $organization;

        return $this->organizations()->whereKey($key)->exists();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return UserFactory::new();
    }
}
