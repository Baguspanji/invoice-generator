<?php

use App\Enums\DocumentationActivityAction;
use App\Enums\DocumentationPageStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\DocumentationActivity;
use App\Models\DocumentationPage;
use App\Models\DocumentationSpace;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskGroup;
use App\Models\User;
use Database\Seeders\DocumentationTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * @return array{0: User, 1: Project}
 */
function documentationContext(): array
{
    $user = User::factory()->create();
    $project = Project::factory()->create();

    return [$user, $project];
}

test('tamu diarahkan ke login saat membuka dokumentasi proyek', function () {
    $project = Project::factory()->create();

    $this->get(route('projects.documentation.show', $project))->assertRedirect('/login');
});

test('customer tidak dapat mengakses workspace dokumentasi', function () {
    $customer = Customer::factory()->create();
    $user = User::factory()->create([
        'role' => UserRole::CUSTOMER->value,
        'customer_id' => $customer->id,
    ]);
    $project = Project::factory()->create(['customer_id' => $customer->id]);

    $this->actingAs($user)
        ->get(route('projects.documentation.show', $project))
        ->assertForbidden();
});

test('staff dapat membuka workspace dan workspace dibuat otomatis', function () {
    [$user, $project] = documentationContext();

    $this->actingAs($user)
        ->get(route('projects.documentation.show', $project))
        ->assertOk()
        ->assertSee('documentation-app');

    expect($project->documentationSpace()->exists())->toBeTrue();
});

test('staff dapat membuat halaman dokumentasi baru', function () {
    [$user, $project] = documentationContext();

    $response = $this->actingAs($user)->post(route('projects.documentation.pages.store', $project), [
        'title' => 'System Architecture',
    ]);

    $space = $project->documentationSpace()->firstOrFail();
    $page = $space->pages()->where('title', 'System Architecture')->first();

    expect($page)->not->toBeNull();
    expect($page->slug)->toBe('system-architecture');
    expect($page->status)->toBe(DocumentationPageStatus::DRAFT);

    $response->assertRedirect(route('projects.documentation.show', ['project' => $project, 'page' => $page->id]));
});

test('autosave memperbarui konten dan menaikkan lock_version', function () {
    [$user, $project] = documentationContext();
    $page = DocumentationPage::factory()->create();

    $content = ['type' => 'doc', 'content' => [
        ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Backend Laravel.']]],
    ]];

    $this->actingAs($user)
        ->patchJson(route('documentation.pages.update', $page), [
            'title' => 'Arsitektur',
            'content' => $content,
            'lock_version' => $page->lock_version,
        ])
        ->assertOk()
        ->assertJsonPath('page.lock_version', 2)
        ->assertJsonPath('page.title', 'Arsitektur');

    $page->refresh();

    expect($page->lock_version)->toBe(2);
    expect($page->content)->toBe($content);
    expect($page->updated_by)->toBe($user->id);
});

test('autosave menolak lock_version yang sudah usang dengan status 409', function () {
    [$user, $project] = documentationContext();
    $page = DocumentationPage::factory()->create(['lock_version' => 1]);

    $this->actingAs($user)
        ->patchJson(route('documentation.pages.update', $page), [
            'lock_version' => 5,
            'title' => 'Konflik',
        ])
        ->assertStatus(409)
        ->assertJsonPath('lock_version', 1);

    expect($page->fresh()->title)->not->toBe('Konflik');
});

test('konten dengan jenis blok yang tidak didukung ditolak', function () {
    [$user, $project] = documentationContext();
    $page = DocumentationPage::factory()->create();

    $this->actingAs($user)
        ->patchJson(route('documentation.pages.update', $page), [
            'content' => ['type' => 'doc', 'content' => [['type' => 'script']]],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('content');
});

test('halaman tidak dapat dijadikan induk bagi dirinya sendiri', function () {
    [$user, $project] = documentationContext();
    $page = DocumentationPage::factory()->create();

    $this->actingAs($user)
        ->patchJson(route('documentation.pages.update', $page), [
            'parent_id' => $page->id,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('parent_id');
});

test('mempublikasikan halaman membuat snapshot versi dan mengubah status', function () {
    [$user, $project] = documentationContext();
    $page = DocumentationPage::factory()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->postJson(route('documentation.pages.publish', $page))
        ->assertOk()
        ->assertJsonPath('page.status.value', 'published');

    $page->refresh();

    expect($page->published_at)->not->toBeNull();
    expect($page->versions()->count())->toBe(1);
    expect($page->versions()->first()->created_by)->toBe($user->id);
});

test('memulihkan versi membuat revisi baru dan mengembalikan status ke draft', function () {
    [$user, $project] = documentationContext();
    $page = DocumentationPage::factory()->create(['title' => 'Versi Lama']);
    $version = $page->versions()->create([
        'version_number' => 1,
        'title' => 'Versi Lama',
        'content' => ['type' => 'doc', 'content' => []],
        'status' => DocumentationPageStatus::PUBLISHED,
        'created_by' => $user->id,
    ]);
    $page->update(['status' => DocumentationPageStatus::PUBLISHED, 'published_at' => now()]);

    $this->actingAs($user)
        ->postJson(route('documentation.versions.restore', ['page' => $page, 'version' => $version]))
        ->assertOk();

    $page->refresh();

    expect($page->status)->toBe(DocumentationPageStatus::DRAFT);
    expect($page->versions()->count())->toBe(2);
});

test('menautkan task dari project lain ditolak', function () {
    [$user, $project] = documentationContext();
    $page = DocumentationPage::factory()->create();
    $otherProject = Project::factory()->create();
    $group = TaskGroup::factory()->create(['project_id' => $otherProject->id]);
    $task = Task::factory()->create(['project_id' => $otherProject->id, 'task_group_id' => $group->id]);

    $this->actingAs($user)
        ->postJson(route('documentation.tasks.store', $page), ['task_id' => $task->id])
        ->assertStatus(422)
        ->assertJsonValidationErrors('task_id');
});

test('menautkan task dari project yang sama berhasil', function () {
    [$user, $project] = documentationContext();
    $space = DocumentationSpace::factory()->create(['project_id' => $project->id]);
    $page = DocumentationPage::factory()->create(['space_id' => $space->id]);
    $group = TaskGroup::factory()->create(['project_id' => $project->id]);
    $task = Task::factory()->create(['project_id' => $project->id, 'task_group_id' => $group->id]);

    $this->actingAs($user)
        ->postJson(route('documentation.tasks.store', $page), ['task_id' => $task->id])
        ->assertOk()
        ->assertJsonCount(1, 'tasks');

    expect($page->tasks()->whereKey($task->id)->exists())->toBeTrue();
});

test('unggah lampiran menyimpan file dan metadata', function () {
    Storage::fake('public');

    [$user, $project] = documentationContext();
    $page = DocumentationPage::factory()->create();

    $this->actingAs($user)
        ->post(route('documentation.attachments.store', $page), [
            'file' => UploadedFile::fake()->image('diagram.png'),
        ], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonPath('attachment.file_name', 'diagram.png');

    $this->assertDatabaseHas('documentation_attachments', [
        'page_id' => $page->id,
        'file_name' => 'diagram.png',
        'uploaded_by' => $user->id,
    ]);
});

test('seeder template mengisi kategori dan halaman untuk workspace kosong', function () {
    $space = DocumentationSpace::factory()->create();

    $this->seed(DocumentationTemplateSeeder::class);

    expect($space->categories()->count())->toBe(5);
    expect($space->pages()->count())->toBe(7);
});

test('menghapus halaman menghapus halaman beserta versinya', function () {
    [$user, $project] = documentationContext();
    $page = DocumentationPage::factory()->create();
    $page->versions()->create([
        'version_number' => 1,
        'title' => $page->title,
        'content' => ['type' => 'doc', 'content' => []],
        'status' => DocumentationPageStatus::DRAFT,
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->delete(route('documentation.pages.destroy', $page))
        ->assertRedirect();

    $this->assertDatabaseMissing('documentation_pages', ['id' => $page->id]);
    $this->assertDatabaseMissing('documentation_versions', ['page_id' => $page->id]);
});

test('membuat halaman mencatat aktivitas pembuatan', function () {
    [$user, $project] = documentationContext();

    $this->actingAs($user)->post(route('projects.documentation.pages.store', $project), [
        'title' => 'API Reference',
    ]);

    $page = $project->documentationSpace()->firstOrFail()->pages()->where('title', 'API Reference')->firstOrFail();

    $this->assertDatabaseHas('documentation_activity_logs', [
        'page_id' => $page->id,
        'user_id' => $user->id,
        'action' => DocumentationActivityAction::CREATED->value,
    ]);
});

test('mempublikasikan halaman mencatat aktivitas publikasi', function () {
    [$user, $project] = documentationContext();
    $page = DocumentationPage::factory()->create();

    $this->actingAs($user)->postJson(route('documentation.pages.publish', $page))->assertOk();

    $this->assertDatabaseHas('documentation_activity_logs', [
        'page_id' => $page->id,
        'action' => DocumentationActivityAction::PUBLISHED->value,
    ]);
});

test('mengirim halaman untuk review mencatat aktivitas', function () {
    [$user, $project] = documentationContext();
    $page = DocumentationPage::factory()->create();

    $this->actingAs($user)->postJson(route('documentation.pages.submit-review', $page))->assertOk();

    $this->assertDatabaseHas('documentation_activity_logs', [
        'page_id' => $page->id,
        'action' => DocumentationActivityAction::SUBMITTED_FOR_REVIEW->value,
    ]);
});

test('endpoint aktivitas mengembalikan log halaman', function () {
    [$user, $project] = documentationContext();
    $page = DocumentationPage::factory()->create();
    DocumentationActivity::factory()->create([
        'space_id' => $page->space_id,
        'page_id' => $page->id,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->getJson(route('documentation.activities.index', $page))
        ->assertOk()
        ->assertJsonCount(1, 'activities')
        ->assertJsonPath('activities.0.user_name', $user->name);
});

test('pencarian menemukan halaman berdasarkan isi konten', function () {
    [$user, $project] = documentationContext();
    $space = DocumentationSpace::factory()->create(['project_id' => $project->id]);
    DocumentationPage::factory()->create([
        'space_id' => $space->id,
        'title' => 'Arsitektur',
        'content' => ['type' => 'doc', 'content' => [
            ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Menggunakan queue Redis untuk antrian email.']]],
        ]],
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('documentation.search', ['q' => 'Redis', 'project_id' => $project->id]))
        ->assertOk()
        ->assertJsonCount(1, 'results')
        ->assertJsonPath('results.0.title', 'Arsitektur');

    expect($response->json('results.0.excerpt'))->toContain('Redis');
});

test('pencarian dapat difilter berdasarkan status', function () {
    [$user, $project] = documentationContext();
    $space = DocumentationSpace::factory()->create(['project_id' => $project->id]);
    DocumentationPage::factory()->create([
        'space_id' => $space->id,
        'title' => 'Draft Doc',
        'status' => DocumentationPageStatus::DRAFT,
    ]);
    DocumentationPage::factory()->published()->create([
        'space_id' => $space->id,
        'title' => 'Published Doc',
    ]);

    $this->actingAs($user)
        ->getJson(route('documentation.search', ['project_id' => $project->id, 'status' => 'published']))
        ->assertOk()
        ->assertJsonCount(1, 'results')
        ->assertJsonPath('results.0.title', 'Published Doc');
});

test('ekspor markdown mengembalikan berkas markdown', function () {
    [$user, $project] = documentationContext();
    $page = DocumentationPage::factory()->create([
        'title' => 'Panduan',
        'content' => ['type' => 'doc', 'content' => [
            ['type' => 'heading', 'attrs' => ['level' => 1], 'content' => [['type' => 'text', 'text' => 'Panduan']]],
            ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Halo dunia.']]],
        ]],
    ]);

    $response = $this->actingAs($user)->get(route('documentation.pages.export.markdown', $page));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('text/markdown');
    expect($response->getContent())->toContain('# Panduan')->toContain('Halo dunia.');
});

test('ekspor pdf mengembalikan berkas pdf', function () {
    [$user, $project] = documentationContext();
    $page = DocumentationPage::factory()->create();

    $response = $this->actingAs($user)->get(route('documentation.pages.export.pdf', $page));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('application/pdf');
    expect(strlen($response->getContent()))->toBeGreaterThan(1000);
});

test('pratinjau mengembalikan html ter-escape dan blok mermaid', function () {
    [$user, $project] = documentationContext();
    $page = DocumentationPage::factory()->create();

    $content = ['type' => 'doc', 'content' => [
        ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => '<script>alert(1)</script>']]],
        ['type' => 'codeBlock', 'attrs' => ['language' => 'mermaid'], 'content' => [['type' => 'text', 'text' => 'graph TD; A-->B;']]],
    ]];

    $response = $this->actingAs($user)
        ->postJson(route('documentation.pages.preview', $page), ['content' => $content])
        ->assertOk();

    $html = $response->json('html');

    expect($html)->toContain('&lt;script&gt;')->not->toContain('<script>');
    expect($html)->toContain('class="mermaid"')->toContain('graph TD; A--&gt;B;');
});
