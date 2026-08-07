<?php

namespace App\Modules\Core\Database\Factories;

use App\Modules\Core\Models\Activity;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Activity>
     */
    protected $model = Activity::class;

    /**
     * Define the model's default state.
     *
     * The subject defaults to an organization because it is the only thing Core
     * has that carries a timeline; a module testing its own records passes its
     * own subject through {@see self::about()}.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject_type' => (new Organization)->getMorphClass(),
            'subject_id' => Organization::factory(),
            'type' => fake()->randomElement(['created', 'updated', 'renamed']),
            'payload' => null,
        ];
    }

    /**
     * An entry about a given record.
     */
    public function about(Model $subject): self
    {
        return $this->state([
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
        ]);
    }

    /**
     * An entry credited to a given account.
     */
    public function by(Model $actor): self
    {
        return $this->state([
            'actor_type' => $actor->getMorphClass(),
            'actor_id' => $actor->getKey(),
        ]);
    }

    /**
     * An entry nobody triggered, as a scheduled command or an expiry leaves.
     */
    public function bySystem(): self
    {
        return $this->state([
            'actor_type' => null,
            'actor_id' => null,
        ]);
    }

    /**
     * An entry credited to a newly made user.
     */
    public function byUser(): self
    {
        return $this->by(User::factory()->create());
    }
}
