<?php

namespace App\Modules\Boards\Database\Factories;

use App\Modules\Boards\Models\Board;
use App\Modules\Core\Models\Space;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Board>
 */
class BoardFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Board>
     */
    protected $model = Board::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'space_id' => Space::factory(),
            'name' => fake()->unique()->words(2, true),
            'position' => 0,
            'is_default' => false,
        ];
    }

    /**
     * The board a business line starts with, for work belonging to no process.
     */
    public function default(): self
    {
        return $this->state(['is_default' => true]);
    }

    /**
     * A board in a space that already exists.
     */
    public function in(Space $space): self
    {
        return $this->state(['space_id' => $space->getKey()]);
    }
}
