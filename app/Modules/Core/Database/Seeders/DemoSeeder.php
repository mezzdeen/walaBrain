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
        $asmaa = $this->user('Asmaa', 'asmaa@app.com');
        $tysier = $this->user('Tysier', 'tysier@app.com');

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
    private function user(string $name, string $email): User
    {
        return User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
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
