<?php

namespace App\Modules\Core\Database\Factories;

use App\Modules\Core\Models\Space;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Space>
 */
class SpaceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Space>
     */
    protected $model = Space::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'position' => 0,
            'is_default' => false,
        ];
    }

    /**
     * The space every member of the organization can reach.
     */
    public function default(): self
    {
        return $this->state(['is_default' => true]);
    }
}
