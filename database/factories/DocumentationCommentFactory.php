<?php

namespace Database\Factories;

use App\Models\DocumentationComment;
use App\Models\DocumentationPage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentationComment>
 */
class DocumentationCommentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'page_id' => DocumentationPage::factory(),
            'user_id' => User::factory(),
            'body' => fake()->paragraph(),
        ];
    }
}
