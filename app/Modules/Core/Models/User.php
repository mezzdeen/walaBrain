<?php

namespace App\Modules\Core\Models;

use App\Modules\Core\Concerns\HasHashId;
use App\Modules\Core\Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
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
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Role> $roles
 * @property-read Collection<int, Permission> $permissions
 * @property-read Collection<int, Organization> $organizations
 */
#[Appends(['full_name'])]
#[Fillable(['first_name', 'last_name', 'email', 'password', 'locale'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements HasLocalePreference, MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasHashId, HasRoles, Notifiable, PasskeyAuthenticatable, SoftDeletes, TwoFactorAuthenticatable;

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
     * Constrain the query to accounts matching a typed search term.
     *
     * Matches an email from the start and either name as a fragment, and splits
     * on a space so a full name matches first and last together — "Ada Lov" is a
     * first name followed by the start of a surname. LIKE wildcards in the term
     * are escaped so a typed `%` searches for a literal percent rather than
     * matching everything.
     *
     * The one place both the members roster and the invite typeahead express
     * what "matching" means, so the two can never drift apart.
     *
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeMatchingSearch(Builder $query, string $term): Builder
    {
        $escaped = addcslashes($term, '%_\\');

        return $query->where(function (Builder $builder) use ($escaped): void {
            $builder->where('email', 'like', $escaped.'%')
                ->orWhere('first_name', 'like', '%'.$escaped.'%')
                ->orWhere('last_name', 'like', '%'.$escaped.'%');

            if (str_contains($escaped, ' ')) {
                [$first, $last] = explode(' ', $escaped, 2);

                $builder->orWhere(function (Builder $nested) use ($first, $last): void {
                    $nested->where('first_name', 'like', $first.'%')
                        ->where('last_name', 'like', $last.'%');
                });
            }
        });
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
     *
     * @return Factory<self>
     */
    protected static function newFactory(): Factory
    {
        return UserFactory::new();
    }
}
