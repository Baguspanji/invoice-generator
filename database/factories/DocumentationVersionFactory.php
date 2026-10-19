<?php

namespace Database\Factories;

use App\Enums\DocumentationPageStatus;
use App\Models\DocumentationPage;
use App\Models\DocumentationVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentationVersion>
 */
class DocumentationVersionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'page_id' => DocumentationPage::factory(),
            'version_number' => 1,
            'title' => fake()->sentence(3),
            'content' => [
                'type' => 'doc',
                'content' => [],
            ],
            'status' => DocumentationPageStatus::DRAFT,
            'change_summary' => fake()->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
