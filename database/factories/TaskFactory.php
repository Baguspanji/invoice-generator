<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'task_group_id' => TaskGroup::factory(),
            'assigned_to_user_id' => User::factory(),
            'invoice_id' => null,
            'title' => fake()->sentence(4),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
            'description' => fake()->paragraph(),
            'estimation' => fake()->randomFloat(2, 1, 40),
            'fixed_price' => null,
            'billable' => true,
            'order_column' => fake()->numberBetween(0, 20),
            'completed_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'completed_at' => now(),
        ]);
    }
}
