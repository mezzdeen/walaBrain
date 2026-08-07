<?php

namespace App\Modules\Boards\Database\Factories;

use App\Modules\Boards\Enums\FieldType;
use App\Modules\Boards\Models\Board;
use App\Modules\Boards\Models\Field;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Field>
 */
class FieldFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Field>
     */
    protected $model = Field::class;

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
            'type' => FieldType::Text,
            'options' => null,
            'help' => null,
            'is_required' => false,
            'position' => 0,
        ];
    }

    /**
     * A field of a given type, with options where the type needs them.
     *
     * @param  list<string>  $options
     */
    public function ofType(FieldType $type, array $options = []): self
    {
        return $this->state([
            'type' => $type,
            'options' => $type->hasOptions() ? ($options ?: ['one', 'two']) : null,
        ]);
    }

    /**
     * A field on a board that already exists.
     */
    public function on(Board $board): self
    {
        return $this->state(['board_id' => $board->getKey()]);
    }
}
