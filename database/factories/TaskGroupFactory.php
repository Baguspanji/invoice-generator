<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\TaskGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskGroup>
 */
class TaskGroupFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => fake()->randomElement(['To Do', 'In Progress', 'QA', 'Done']),
            'color' => fake()->safeHexColor(),
            'order_column' => fake()->numberBetween(0, 10),
        ];
    }
}
