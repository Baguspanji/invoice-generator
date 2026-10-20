<?php

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create();
    $this->todo = TaskGroup::factory()->create(['project_id' => $this->project->id, 'name' => 'To Do']);
    $this->done = TaskGroup::factory()->create(['project_id' => $this->project->id, 'name' => 'Done']);
});

test('pengguna dapat menambahkan subtask yang mewarisi group parent', function () {
    $parent = Task::factory()->create(['project_id' => $this->project->id, 'task_group_id' => $this->todo->id]);

    $this->actingAs($this->user)->post(route('projects.tasks.store', $this->project), [
        'parent_id' => $parent->id,
        'title' => 'Buat migration',
    ])->assertRedirect(route('projects.show', $this->project));

    $subtask = Task::where('parent_id', $parent->id)->first();
    expect($subtask)->not->toBeNull()
        ->and($subtask->task_group_id)->toBe($this->todo->id)
        ->and($subtask->project_id)->toBe($this->project->id);
});

test('subtask tidak bisa dibuat dari subtask lain', function () {
    $parent = Task::factory()->create(['project_id' => $this->project->id, 'task_group_id' => $this->todo->id]);
    $subtask = Task::factory()->subtaskOf($parent)->create();

    $this->actingAs($this->user)->post(route('projects.tasks.store', $this->project), [
        'parent_id' => $subtask->id,
        'title' => 'Subtask bertingkat',
    ])->assertSessionHasErrors('parent_id');

    expect(Task::where('title', 'Subtask bertingkat')->exists())->toBeFalse();
});

test('parent dari project lain ditolak saat membuat subtask', function () {
    $otherProject = Project::factory()->create();
    $otherGroup = TaskGroup::factory()->create(['project_id' => $otherProject->id]);
    $foreignParent = Task::factory()->create(['project_id' => $otherProject->id, 'task_group_id' => $otherGroup->id]);

    $this->actingAs($this->user)->post(route('projects.tasks.store', $this->project), [
        'parent_id' => $foreignParent->id,
        'title' => 'Subtask lintas project',
    ])->assertSessionHasErrors('parent_id');
});

test('task yang sudah punya subtask tidak bisa diselesaikan selama subtask masih terbuka', function () {
    $parent = Task::factory()->create(['project_id' => $this->project->id, 'task_group_id' => $this->todo->id]);
    Task::factory()->subtaskOf($parent)->create();

    $this->actingAs($this->user)->put(route('projects.tasks.update', [$this->project, $parent]), [
        'title' => $parent->title,
        'completed' => '1',
    ])->assertSessionHasErrors('completed');

    expect($parent->fresh()->isCompleted())->toBeFalse();
});

test('parent dapat diselesaikan setelah semua subtask selesai', function () {
    $parent = Task::factory()->create(['project_id' => $this->project->id, 'task_group_id' => $this->todo->id]);
    Task::factory()->subtaskOf($parent)->completed()->create();

    $this->actingAs($this->user)->put(route('projects.tasks.update', [$this->project, $parent]), [
        'title' => $parent->title,
        'completed' => '1',
    ])->assertRedirect();

    expect($parent->fresh()->isCompleted())->toBeTrue();
});

test('memindahkan parent ikut memindahkan subtask-nya', function () {
    $doing = TaskGroup::factory()->create(['project_id' => $this->project->id, 'name' => 'Doing']);
    $parent = Task::factory()->create(['project_id' => $this->project->id, 'task_group_id' => $this->todo->id]);
    $subtask = Task::factory()->subtaskOf($parent)->create();

    $this->actingAs($this->user)->postJson(route('projects.tasks.move', $this->project), [
        'task_id' => $parent->id,
        'task_group_id' => $doing->id,
        'order_column' => 1,
    ])->assertOk();

    expect($subtask->fresh()->task_group_id)->toBe($doing->id);
});

test('subtask tidak bisa dipindahkan secara terpisah', function () {
    $parent = Task::factory()->create(['project_id' => $this->project->id, 'task_group_id' => $this->todo->id]);
    $subtask = Task::factory()->subtaskOf($parent)->create();

    $this->actingAs($this->user)->postJson(route('projects.tasks.move', $this->project), [
        'task_id' => $subtask->id,
        'task_group_id' => $this->done->id,
        'order_column' => 1,
    ])->assertUnprocessable();

    expect($subtask->fresh()->task_group_id)->toBe($this->todo->id);
});

test('parent dengan subtask terbuka tidak bisa dipindahkan ke Done', function () {
    $parent = Task::factory()->create(['project_id' => $this->project->id, 'task_group_id' => $this->todo->id]);
    Task::factory()->subtaskOf($parent)->create();

    $this->actingAs($this->user)->postJson(route('projects.tasks.move', $this->project), [
        'task_id' => $parent->id,
        'task_group_id' => $this->done->id,
        'order_column' => 1,
    ])->assertUnprocessable();

    expect($parent->fresh()->task_group_id)->toBe($this->todo->id);
});

test('menghapus parent ikut menghapus subtask dan lampirannya', function () {
    $parent = Task::factory()->create(['project_id' => $this->project->id, 'task_group_id' => $this->todo->id]);
    $subtask = Task::factory()->subtaskOf($parent)->create();

    $this->actingAs($this->user)->delete(route('projects.tasks.destroy', [$this->project, $parent]))
        ->assertRedirect();

    expect(Task::whereKey($subtask->id)->exists())->toBeFalse();
});

test('halaman proyek hanya menampilkan task utama dengan subtask bersarang', function () {
    $parent = Task::factory()->create([
        'project_id' => $this->project->id,
        'task_group_id' => $this->todo->id,
        'title' => 'Induk Task',
    ]);
    Task::factory()->subtaskOf($parent)->create(['title' => 'Anak Subtask']);

    $response = $this->actingAs($this->user)->get(route('projects.show', $this->project));

    $response->assertOk();
    $group = $response->viewData('project')->taskGroups->firstWhere('id', $this->todo->id);
    expect($group->tasks)->toHaveCount(1)
        ->and($group->tasks->first()->subtasks)->toHaveCount(1);
});

test('filter menampilkan induk jika subtask yang cocok', function () {
    $parent = Task::factory()->create([
        'project_id' => $this->project->id,
        'task_group_id' => $this->todo->id,
        'title' => 'Induk Biasa',
    ]);
    Task::factory()->subtaskOf($parent)->create(['title' => 'Integrasi Pembayaran']);
    Task::factory()->create([
        'project_id' => $this->project->id,
        'task_group_id' => $this->todo->id,
        'title' => 'Tugas Lain',
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('projects.show', ['project' => $this->project, 'search' => 'Integrasi']));

    $group = $response->viewData('project')->taskGroups->firstWhere('id', $this->todo->id);
    expect($group->tasks)->toHaveCount(1)
        ->and($group->tasks->first()->title)->toBe('Induk Biasa')
        ->and($group->tasks->first()->subtasks)->toHaveCount(1);
});

test('portal customer melihat subtask bersarang di bawah task utama', function () {
    $customer = Customer::factory()->create();
    $project = Project::factory()->create(['customer_id' => $customer->id]);
    $group = TaskGroup::factory()->create(['project_id' => $project->id]);
    $parent = Task::factory()->create(['project_id' => $project->id, 'task_group_id' => $group->id, 'title' => 'Induk Portal']);
    Task::factory()->subtaskOf($parent)->create(['title' => 'Anak Portal']);

    $customerUser = User::factory()->create([
        'role' => UserRole::CUSTOMER->value,
        'customer_id' => $customer->id,
    ]);

    $this->actingAs($customerUser)
        ->get(route('portal.projects.show', $project))
        ->assertOk()
        ->assertSee('Induk Portal')
        ->assertSee('Anak Portal')
        ->assertSee('0/1 subtask');
});

test('halaman board mengirim subtask ke konfigurasi frontend', function () {
    $parent = Task::factory()->create(['project_id' => $this->project->id, 'task_group_id' => $this->todo->id]);
    Task::factory()->subtaskOf($parent)->create(['title' => 'Subtask Board']);

    $this->actingAs($this->user)
        ->get(route('projects.show', $this->project))
        ->assertOk()
        ->assertSee('Subtask Board')
        ->assertSee('"subtasks"', false);
});
