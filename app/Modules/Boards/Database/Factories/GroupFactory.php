<?php

namespace App\Modules\Boards\Database\Factories;

use App\Modules\Boards\Models\Board;
use App\Modules\Boards\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Group>
     */
    protected $model = Group::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'board_id' => Board::factory(),
            'name' => fake()->unique()->words(2, true),
            'position' => 0,
        ];
    }

    /**
     * A group on a board that already exists.
     */
    public function on(Board $board): self
    {
        return $this->state(['board_id' => $board->getKey()]);
    }
}
