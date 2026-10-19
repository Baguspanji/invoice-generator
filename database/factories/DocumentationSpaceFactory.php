<?php

namespace Database\Factories;

use App\Models\DocumentationSpace;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentationSpace>
 */
class DocumentationSpaceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => 'Dokumentasi '.fake()->word(),
            'description' => fake()->paragraph(),
            'created_by' => User::factory(),
        ];
    }
}
