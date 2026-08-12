<?php

namespace App\Modules\Forms\Database\Factories;

use App\Modules\Boards\Models\Board;
use App\Modules\Forms\Models\Form;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Form>
 */
class FormFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Form>
     */
    protected $model = Form::class;

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
            'prefix' => strtoupper(fake()->unique()->lexify('???')),
            'version' => 1,
            'published_at' => now(),
        ];
    }

    /**
     * A form still being drafted, which accepts nothing.
     */
    public function draft(): self
    {
        return $this->state(['published_at' => null]);
    }

    /**
     * A form on a board that already exists.
     */
    public function on(Board $board): self
    {
        return $this->state(['board_id' => $board->getKey()]);
    }
}
