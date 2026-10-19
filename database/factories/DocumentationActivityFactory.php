<?php

namespace Database\Factories;

use App\Enums\DocumentationActivityAction;
use App\Models\DocumentationActivity;
use App\Models\DocumentationPage;
use App\Models\DocumentationSpace;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentationActivity>
 */
class DocumentationActivityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'space_id' => DocumentationSpace::factory(),
            'page_id' => DocumentationPage::factory(),
            'user_id' => User::factory(),
            'action' => fake()->randomElement(DocumentationActivityAction::cases())->value,
            'description' => fake()->sentence(),
            'properties' => null,
        ];
    }
}
