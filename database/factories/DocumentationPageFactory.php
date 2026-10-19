<?php

namespace Database\Factories;

use App\Enums\DocumentationPageStatus;
use App\Models\DocumentationPage;
use App\Models\DocumentationSpace;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DocumentationPage>
 */
class DocumentationPageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = Str::title(fake()->unique()->words(3, true));

        return [
            'space_id' => DocumentationSpace::factory(),
            'category_id' => null,
            'parent_id' => null,
            'title' => $title,
            'slug' => Str::slug($title),
            'content' => [
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'paragraph',
                        'content' => [['type' => 'text', 'text' => fake()->sentence()]],
                    ],
                ],
            ],
            'status' => DocumentationPageStatus::DRAFT,
            'created_by' => User::factory(),
            'updated_by' => null,
            'published_at' => null,
            'lock_version' => 1,
            'order_column' => fake()->numberBetween(0, 10),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DocumentationPageStatus::PUBLISHED,
            'published_at' => now(),
        ]);
    }
}
