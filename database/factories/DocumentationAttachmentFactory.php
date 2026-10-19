<?php

namespace Database\Factories;

use App\Models\DocumentationAttachment;
use App\Models\DocumentationPage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentationAttachment>
 */
class DocumentationAttachmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'page_id' => DocumentationPage::factory(),
            'uploaded_by' => User::factory(),
            'disk' => 'public',
            'file_path' => 'documentation-attachments/'.fake()->uuid().'.png',
            'file_name' => fake()->word().'.png',
            'file_size' => fake()->numberBetween(1000, 500000),
            'mime_type' => 'image/png',
        ];
    }
}
