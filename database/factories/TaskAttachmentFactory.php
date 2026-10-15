<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\TaskAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskAttachment>
 */
class TaskAttachmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'file_name' => fake()->word().'.pdf',
            'file_path' => 'attachments/'.fake()->uuid().'.pdf',
            'file_size' => fake()->numberBetween(1024, 5242880),
            'mime_type' => 'application/pdf',
        ];
    }
}
