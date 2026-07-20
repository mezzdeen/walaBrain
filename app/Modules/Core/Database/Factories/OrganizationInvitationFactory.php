<?php

namespace App\Modules\Core\Database\Factories;

use App\Modules\Core\Enums\OrganizationRole;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\OrganizationInvitation;
use App\Modules\Core\Support\OrganizationInvitations;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<OrganizationInvitation>
 */
class OrganizationInvitationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<OrganizationInvitation>
     */
    protected $model = OrganizationInvitation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'email' => fake()->unique()->safeEmail(),
            'role' => OrganizationRole::Owner,
            'token' => OrganizationInvitations::hash(Str::random(64)),
            'invited_by_admin_id' => null,
            'expires_at' => now()->addDays(OrganizationInvitations::EXPIRES_AFTER_DAYS),
            'accepted_at' => null,
        ];
    }

    /**
     * An invitation whose window has already closed.
     */
    public function expired(): static
    {
        return $this->state(fn (): array => [
            'expires_at' => now()->subDay(),
        ]);
    }

    /**
     * An invitation that has already been used.
     */
    public function accepted(): static
    {
        return $this->state(fn (): array => [
            'accepted_at' => now(),
        ]);
    }
}
