<?php

namespace Database\Factories;

use App\Models\DocumentationCategory;
use App\Models\DocumentationSpace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DocumentationCategory>
 */
class DocumentationCategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'space_id' => DocumentationSpace::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'order_column' => fake()->numberBetween(0, 10),
        ];
    }
}
