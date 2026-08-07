<?php

namespace App\Modules\Boards\Database\Factories;

use App\Modules\Boards\Models\Board;
use App\Modules\Boards\Models\Group;
use App\Modules\Boards\Models\Node;
use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Node>
 */
class NodeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Node>
     */
    protected $model = Node::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'board_id' => Board::factory(),
            'title' => fake()->sentence(4),
            'description' => null,
            'assignee_id' => null,
            'due_date' => null,
            'status' => null,
            'values' => null,
        ];
    }

    /**
     * A node on a board that already exists.
     */
    public function on(Board $board): self
    {
        return $this->state(['board_id' => $board->getKey()]);
    }

    /**
     * A node displayed in a given group.
     */
    public function inGroup(Group $group): self
    {
        return $this->state([
            'board_id' => $group->board_id,
            'group_id' => $group->getKey(),
        ]);
    }

    /**
     * A node waiting on somebody, optionally by a date.
     */
    public function assignedTo(User $user, ?string $dueDate = null): self
    {
        return $this->state([
            'assignee_id' => $user->getKey(),
            'due_date' => $dueDate,
        ]);
    }
}
