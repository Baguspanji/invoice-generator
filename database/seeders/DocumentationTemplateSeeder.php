<?php

namespace Database\Seeders;

use App\Enums\DocumentationPageStatus;
use App\Models\DocumentationSpace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Mengisi kategori default dan halaman template untuk workspace dokumentasi
 * yang sudah ada. Idempotent: hanya mengisi bagian yang masih kosong.
 */
class DocumentationTemplateSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    private const CATEGORIES = [
        'Architecture',
        'API',
        'Database',
        'Business Process',
        'Deployment',
    ];

    /**
     * @var list<string>
     */
    private const PAGES = [
        'Project Overview',
        'Architecture',
        'Modules & Business Flow',
        'Database Design',
        'API Reference',
        'Development Setup',
        'Deployment & Operations',
    ];

    public function run(): void
    {
        DocumentationSpace::query()->each(function (DocumentationSpace $space): void {
            if ($space->categories()->doesntExist()) {
                foreach (self::CATEGORIES as $index => $name) {
                    $space->categories()->create([
                        'name' => $name,
                        'slug' => Str::slug($name),
                        'order_column' => $index,
                    ]);
                }
            }

            if ($space->pages()->doesntExist()) {
                foreach (self::PAGES as $index => $title) {
                    $space->pages()->create([
                        'title' => $title,
                        'slug' => Str::slug($title),
                        'content' => ['type' => 'doc', 'content' => []],
                        'status' => DocumentationPageStatus::DRAFT,
                        'created_by' => $space->created_by,
                        'order_column' => $index + 1,
                    ]);
                }
            }
        });
    }
}
