<?php

use App\Enums\TaskPriority;
use App\Models\Customer;
use App\Models\Label;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskGroup;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('tamu diarahkan ke login saat membuka halaman proyek', function () {
    $this->get('/projects')->assertRedirect('/login');
});

test('pengguna terotentikasi dapat melihat daftar proyek', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['name' => 'Aplikasi Kasir Mobile']);

    $this->actingAs($user)
        ->get('/projects')
        ->assertOk()
        ->assertSee('Aplikasi Kasir Mobile');
});

test('pengguna dapat membuat proyek baru dan grup task default diinisialisasi', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();

    $response = $this->actingAs($user)->post('/projects', [
        'customer_id' => $customer->id,
        'name' => 'Sistem Rekam Medis',
        'rate' => 150000,
        'pricing_type' => 'hourly',
        'description' => 'Implementasi modul rekam medis elektronik.',
        'user_ids' => [$user->id],
    ]);

    $project = Project::where('name', 'Sistem Rekam Medis')->first();
    expect($project)->not->toBeNull();
    expect($project->taskGroups()->count())->toBe(4);
    expect($project->users()->count())->toBe(1);

    $response->assertRedirect(route('projects.show', $project));
});

test('pengguna dapat menambahkan task ke proyek', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $group = TaskGroup::factory()->create(['project_id' => $project->id]);

    $response = $this->actingAs($user)->post(route('projects.tasks.store', $project), [
        'task_group_id' => $group->id,
        'title' => 'Setup Database Schema',
        'description' => 'Migrate initial tables',
        'estimation' => 4,
        'billable' => 1,
    ]);

    $response->assertRedirect(route('projects.show', $project));
    expect($project->tasks()->where('title', 'Setup Database Schema')->exists())->toBeTrue();
});

test('task baru secara default memiliki status billable false', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $group = TaskGroup::factory()->create(['project_id' => $project->id]);

    $this->actingAs($user)->post(route('projects.tasks.store', $project), [
        'task_group_id' => $group->id,
        'title' => 'Internal Task Non Billable',
    ])->assertRedirect(route('projects.show', $project));

    $task = $project->tasks()->where('title', 'Internal Task Non Billable')->first();
    expect($task)->not->toBeNull();
    expect($task->billable)->toBeFalse();
});

test('pengguna dapat memulai dan menghentikan timer pada task', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $group = TaskGroup::factory()->create(['project_id' => $project->id]);
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'task_group_id' => $group->id,
    ]);

    // Start timer
    $this->actingAs($user)->post(route('projects.tasks.time-log.store', [$project, $task]), [
        'action' => 'start_timer',
    ])->assertRedirect(route('projects.show', $project));

    $log = TimeLog::where('task_id', $task->id)->where('user_id', $user->id)->first();
    expect($log)->not->toBeNull();
    expect($log->isRunning())->toBeTrue();

    // Stop timer
    $this->actingAs($user)->post(route('projects.tasks.time-log.store', [$project, $task]), [
        'action' => 'stop_timer',
    ])->assertRedirect(route('projects.show', $project));

    $log->refresh();
    expect($log->isRunning())->toBeFalse();
    expect($log->minutes)->toBeGreaterThanOrEqual(1);
});

test('pengguna dapat memindahkan task antar task group', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $group1 = TaskGroup::factory()->create(['project_id' => $project->id, 'name' => 'To Do']);
    $groupDone = TaskGroup::factory()->create(['project_id' => $project->id, 'name' => 'Done']);
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'task_group_id' => $group1->id,
        'completed_at' => null,
    ]);

    $response = $this->actingAs($user)->postJson(route('projects.tasks.move', $project), [
        'task_id' => $task->id,
        'task_group_id' => $groupDone->id,
        'order_column' => 1,
    ]);

    $response->assertOk()->assertJson(['success' => true]);

    $task->refresh();
    expect($task->task_group_id)->toBe($groupDone->id);
    expect($task->isCompleted())->toBeTrue();
});

test('pengguna dapat melihat proyek dalam mode list view', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $group = TaskGroup::factory()->create(['project_id' => $project->id, 'name' => 'Backlog']);
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'task_group_id' => $group->id,
        'title' => 'List View Specific Task',
    ]);

    $this->actingAs($user)
        ->get(route('projects.show', [$project, 'view' => 'list']))
        ->assertOk()
        ->assertSee('List View Specific Task')
        ->assertSee('Backlog');
});

test('pengguna dapat mencari dan memfilter task pada mode list view', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $group = TaskGroup::factory()->create(['project_id' => $project->id]);

    $task1 = Task::factory()->create([
        'project_id' => $project->id,
        'task_group_id' => $group->id,
        'title' => 'Integrasi Payment Gateway',
        'priority' => TaskPriority::URGENT,
        'billable' => true,
    ]);

    $task2 = Task::factory()->create([
        'project_id' => $project->id,
        'task_group_id' => $group->id,
        'title' => 'Desain Halaman Profil',
        'priority' => TaskPriority::LOW,
        'billable' => false,
    ]);

    // Search query
    $responseSearch = $this->actingAs($user)
        ->get(route('projects.show', [$project, 'view' => 'list', 'search' => 'Payment']))
        ->assertOk();

    expect($responseSearch->viewData('project')->taskGroups->first()->tasks->pluck('title')->all())
        ->toBe(['Integrasi Payment Gateway']);

    // Priority filter
    $responsePriority = $this->actingAs($user)
        ->get(route('projects.show', [$project, 'view' => 'list', 'priority' => 'low']))
        ->assertOk();

    expect($responsePriority->viewData('project')->taskGroups->first()->tasks->pluck('title')->all())
        ->toBe(['Desain Halaman Profil']);

    // Billing filter
    $responseBilling = $this->actingAs($user)
        ->get(route('projects.show', [$project, 'view' => 'list', 'billing' => 'non_billable']))
        ->assertOk();

    expect($responseBilling->viewData('project')->taskGroups->first()->tasks->pluck('title')->all())
        ->toBe(['Desain Halaman Profil']);
});

test('pengguna dapat memperbarui task dari list view', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $group1 = TaskGroup::factory()->create(['project_id' => $project->id]);
    $group2 = TaskGroup::factory()->create(['project_id' => $project->id]);
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'task_group_id' => $group1->id,
        'title' => 'Original Task Title',
    ]);

    $this->actingAs($user)->put(route('projects.tasks.update', [$project, $task]), [
        'title' => 'Judul Task Baru',
        'task_group_id' => $group2->id,
        'completed' => 1,
    ])->assertRedirect(route('projects.show', $project));

    $task->refresh();
    expect($task->title)->toBe('Judul Task Baru');
    expect($task->task_group_id)->toBe($group2->id);
    expect($task->isCompleted())->toBeTrue();
});

test('pengguna dapat menambahkan task dengan prioritas, label, dan lampiran file', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $project = Project::factory()->create();
    $group = TaskGroup::factory()->create(['project_id' => $project->id]);
    $label = Label::factory()->create(['name' => 'Urgent Bug']);
    $file = UploadedFile::fake()->create('spec.pdf', 500, 'application/pdf');

    $response = $this->actingAs($user)->post(route('projects.tasks.store', $project), [
        'task_group_id' => $group->id,
        'title' => 'Fix Crash on Payment',
        'priority' => 'urgent',
        'label_ids' => [$label->id],
        'attachments' => [$file],
    ]);

    $response->assertRedirect(route('projects.show', $project));

    $task = $project->tasks()->where('title', 'Fix Crash on Payment')->first();
    expect($task)->not->toBeNull();
    expect($task->priority)->toBe(TaskPriority::URGENT);
    expect($task->labels->pluck('id')->all())->toContain($label->id);
    expect($task->attachments)->toHaveCount(1);
    expect($task->attachments->first()->file_name)->toBe('spec.pdf');

    Storage::disk('public')->assertExists($task->attachments->first()->file_path);
});

test('pengguna dapat memperbarui prioritas, label, dan mengunggah lampiran baru pada task', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $project = Project::factory()->create();
    $group = TaskGroup::factory()->create(['project_id' => $project->id]);
    $label1 = Label::factory()->create(['name' => 'UI']);
    $label2 = Label::factory()->create(['name' => 'Backend']);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'task_group_id' => $group->id,
        'priority' => 'low',
    ]);
    $task->labels()->attach($label1->id);

    $file = UploadedFile::fake()->image('mockup.png');

    $response = $this->actingAs($user)->put(route('projects.tasks.update', [$project, $task]), [
        'title' => 'Updated Task Title',
        'priority' => 'high',
        'sync_labels' => 1,
        'label_ids' => [$label2->id],
        'attachments' => [$file],
    ]);

    $response->assertRedirect(route('projects.show', $project));

    $task->refresh();
    expect($task->title)->toBe('Updated Task Title');
    expect($task->priority)->toBe(TaskPriority::HIGH);
    expect($task->labels->pluck('id')->all())->toEqual([$label2->id]);
    expect($task->attachments)->toHaveCount(1);
    expect($task->attachments->first()->file_name)->toBe('mockup.png');

    Storage::disk('public')->assertExists($task->attachments->first()->file_path);
});

test('pengguna dapat menghapus lampiran pada task', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $project = Project::factory()->create();
    $group = TaskGroup::factory()->create(['project_id' => $project->id]);
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'task_group_id' => $group->id,
    ]);

    $filePath = 'task-attachments/sample.pdf';
    Storage::disk('public')->put($filePath, 'dummy content');

    $attachment = TaskAttachment::factory()->create([
        'task_id' => $task->id,
        'file_name' => 'sample.pdf',
        'file_path' => $filePath,
    ]);

    $response = $this->actingAs($user)->delete(
        route('projects.tasks.attachments.destroy', [$project, $task, $attachment])
    );

    $response->assertRedirect(route('projects.show', $project));
    expect(TaskAttachment::find($attachment->id))->toBeNull();
    Storage::disk('public')->assertMissing($filePath);
});
