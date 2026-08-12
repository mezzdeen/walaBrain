<?php

namespace App\Modules\Flows\Database\Factories;

use App\Modules\Boards\Models\Board;
use App\Modules\Flows\Models\Flow;
use App\Modules\Forms\Models\Form;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Flow>
 */
class FlowFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Flow>
     */
    protected $model = Flow::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Tests build flows through for_(), which keeps the flow on the board
        // its form creates nodes on; the bare definition exists for the rare
        // test that cares about neither.
        return [
            'board_id' => Board::factory(),
            'form_id' => Form::factory(),
            'name' => fake()->unique()->words(2, true),
            'version' => 1,
            'published_at' => now(),
        ];
    }

    /**
     * A flow triggered by a form that already exists, acting on its board.
     */
    public function for_(Form $form): self
    {
        return $this->state([
            'form_id' => $form->getKey(),
            'board_id' => $form->board_id,
        ]);
    }
}
