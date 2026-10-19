<?php

use App\Enums\DocumentationPageStatus;
use App\Enums\UserRole;
use App\Models\DocumentationActivity;
use App\Models\DocumentationAttachment;
use App\Models\DocumentationComment;
use App\Models\DocumentationPage;
use App\Models\DocumentationSpace;
use App\Models\DocumentationVersion;
use App\Models\Project;
use App\Models\User;
use App\Services\Documentation\ContentValidationService;
use Database\Seeders\DocumentationDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

const DEMO_PROJECT_NAME = 'Aplikasi E-Invoice IceSum';

function seedDocumentationDemo(): void
{
    Storage::fake('public');

    app(DocumentationDemoSeeder::class)->run();
}

test('seeder dokumentasi demo membuat project beserta dokumentasi lengkap', function () {
    seedDocumentationDemo();

    $project = Project::where('name', DEMO_PROJECT_NAME)->firstOrFail();
    $space = $project->documentationSpace;
    $pageIds = $space->pages()->pluck('id');

    expect($project->users()->count())->toBe(3)
        ->and($project->taskGroups()->count())->toBe(4)
        ->and($project->tasks()->count())->toBe(9)
        ->and($space)->not->toBeNull()
        ->and($space->categories()->count())->toBe(5)
        ->and($space->pages()->count())->toBe(11)
        ->and($space->pages()->whereNull('parent_id')->count())->toBe(7)
        ->and($space->pages()->whereNotNull('parent_id')->count())->toBe(4)
        ->and(DocumentationVersion::whereIn('page_id', $pageIds)->count())->toBeGreaterThanOrEqual(11)
        ->and(DocumentationComment::whereIn('page_id', $pageIds)->count())->toBeGreaterThan(0)
        ->and(DocumentationActivity::where('space_id', $space->id)->count())->toBeGreaterThan(0)
        ->and(DocumentationAttachment::whereIn('page_id', $pageIds)->count())->toBeGreaterThan(0)
        ->and(DB::table('documentation_page_task')->whereIn('page_id', $pageIds)->count())->toBeGreaterThan(0);
});

test('seeder dokumentasi demo mencakup halaman published, in review, dan draft', function () {
    seedDocumentationDemo();

    $statuses = DocumentationPage::query()->pluck('status');

    expect($statuses->contains(DocumentationPageStatus::PUBLISHED))->toBeTrue()
        ->and($statuses->contains(DocumentationPageStatus::IN_REVIEW))->toBeTrue()
        ->and($statuses->contains(DocumentationPageStatus::DRAFT))->toBeTrue();
});

test('seeder dokumentasi demo membuat user staf demo', function () {
    seedDocumentationDemo();

    $demoEmails = ['dev@example.com', 'qa@example.com'];

    expect(User::whereIn('email', $demoEmails)->count())->toBe(2)
        ->and(User::whereIn('email', $demoEmails)->pluck('role')->unique()->all())
        ->toBe([UserRole::STAFF]);
});

test('seluruh konten halaman hasil seeder lolos validasi editor', function () {
    seedDocumentationDemo();

    $service = app(ContentValidationService::class);

    expect(fn () => DocumentationPage::query()->each(
        fn (DocumentationPage $page) => $service->validate($page->content),
    ))->not->toThrow(Throwable::class);
});

test('menjalankan seeder dokumentasi demo dua kali tidak menggandakan data', function () {
    seedDocumentationDemo();

    $this->seed(DocumentationDemoSeeder::class);

    expect(Project::where('name', DEMO_PROJECT_NAME)->count())->toBe(1)
        ->and(DocumentationSpace::count())->toBe(1)
        ->and(DocumentationPage::count())->toBe(11);
});
