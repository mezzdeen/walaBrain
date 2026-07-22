<?php

namespace App\Modules\Core\Database\Seeders;

use App\Modules\Core\Enums\OrganizationRole;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\OrganizationOwners;
use App\Modules\Core\Support\OrganizationRoles;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Two accounts, two organizations, and swapped roles between them.
 *
 * The point of the arrangement is that neither account is simply "the admin" or
 * "the employee": each one owns one organization and is an ordinary member of
 * the other. That is what makes it useful for working on the organization
 * switcher and on anything permission shaped, because the same signed-in user
 * sees a different set of abilities depending on which organization is active.
 */
class DemoSeeder extends Seeder
{
    /**
     * The password both demo accounts sign in with.
     */
    private const PASSWORD = 'password';

    /**
     * Seed the demo accounts and organizations.
     */
    public function run(): void
    {
        // Known-password accounts that own organizations exist only to make
        // local work easier, so they must never be created on a real
        // deployment — a `db:seed --force` there would hand anyone who reads
        // this repository a tenant owner's credentials.
        if (app()->isProduction()) {
            return;
        }

        $asmaa = $this->user('Asmaa', 'Izz', 'asmaa@app.com');
        $tysier = $this->user('Tysier', 'Saleh', 'tysier@app.com');

        $this->organization('Nakheel', owner: $asmaa, member: $tysier);

        // The mirror image: whoever was the employee above runs this one.
        $this->organization('Rawabi', owner: $tysier, member: $asmaa);
    }

    /**
     * Find or create a demo account with a known password.
     *
     * Verified on creation so the demo accounts can sign in without first
     * having to collect a verification mail.
     */
    private function user(string $firstName, string $lastName, string $email): User
    {
        // Bring back a closed demo account rather than colliding with the email
        // it still reserves: a soft delete leaves the row, and the unique index
        // with it, so a re-run has to restore before it can match.
        $trashed = User::withTrashed()->firstWhere('email', $email);

        if ($trashed?->trashed()) {
            $trashed->restore();
        }

        return User::firstOrCreate(
            ['email' => $email],
            [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'password' => Hash::make(self::PASSWORD),
                'email_verified_at' => now(),
            ],
        );
    }

    /**
     * Create the organization, provision its roles, and staff it.
     *
     * Safe to run more than once: the organization is matched by name, and both
     * role assignments are additive, so re-seeding cannot strip either account
     * of what it holds in the other organization.
     */
    private function organization(string $name, User $owner, User $member): void
    {
        // Restore a soft-deleted organization instead of seeding a second one
        // of the same name: matched by name, `firstOrCreate` cannot see the
        // trashed row and would otherwise leave two behind.
        $trashed = Organization::withTrashed()->firstWhere('name', $name);

        if ($trashed?->trashed()) {
            $trashed->restore();
        }

        $organization = Organization::firstOrCreate(['name' => $name]);

        OrganizationRoles::provision($organization);
        OrganizationOwners::assign($organization, $owner);

        $organization->users()->syncWithoutDetaching([$member->getKey()]);

        // Roles are team owned, so the member role has to be resolved with the
        // permission team pointed at this organization.
        OrganizationRoles::within($organization, function () use ($member): void {
            $member->assignRole(OrganizationRole::Member->value);
        });
    }
}
