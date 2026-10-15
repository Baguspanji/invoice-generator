<?php

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskGroup;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('pengguna dapat menerbitkan invoice dari task proyek hourly berdasarkan time log', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();
    $project = Project::factory()->create([
        'customer_id' => $customer->id,
        'rate' => 100000,
        'pricing_type' => 'hourly',
    ]);
    $group = TaskGroup::factory()->create(['project_id' => $project->id]);

    $task1 = Task::factory()->create([
        'project_id' => $project->id,
        'task_group_id' => $group->id,
        'title' => 'Feature Auth',
        'billable' => true,
        'invoice_id' => null,
    ]);

    // 120 minutes = 2 hours => 2 * 100.000 = 200.000
    TimeLog::factory()->create([
        'task_id' => $task1->id,
        'user_id' => $user->id,
        'minutes' => 120,
    ]);

    $response = $this->actingAs($user)->post(route('projects.invoices.generate', $project), [
        'task_ids' => [$task1->id],
        'due_date' => now()->addDays(7)->toDateString(),
    ]);

    $task1->refresh();
    expect($task1->invoice_id)->not->toBeNull();

    $invoice = Invoice::find($task1->invoice_id);
    expect($invoice)->not->toBeNull();
    expect($invoice->customer_id)->toBe($customer->id);
    expect((float) $invoice->total_amount)->toBe(200000.0);
    expect($invoice->items()->count())->toBe(1);

    $response->assertRedirect(route('invoices.show', $invoice));
});

test('pengguna dapat menerbitkan invoice dari task proyek fixed price', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();
    $project = Project::factory()->create([
        'customer_id' => $customer->id,
        'rate' => 0,
        'pricing_type' => 'fixed',
    ]);
    $group = TaskGroup::factory()->create(['project_id' => $project->id]);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'task_group_id' => $group->id,
        'title' => 'Desain Landing Page',
        'fixed_price' => 1500000,
        'billable' => true,
        'invoice_id' => null,
    ]);

    $this->actingAs($user)->post(route('projects.invoices.generate', $project), [
        'task_ids' => [$task->id],
    ]);

    $task->refresh();
    expect($task->invoice_id)->not->toBeNull();

    $invoice = Invoice::find($task->invoice_id);
    expect((float) $invoice->total_amount)->toBe(1500000.0);
});

test('task yang sudah memiliki invoice tidak dapat ditagihkan dua kali', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();
    $project = Project::factory()->create(['customer_id' => $customer->id]);
    $existingInvoice = Invoice::factory()->create(['customer_id' => $customer->id]);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'invoice_id' => $existingInvoice->id,
        'billable' => true,
    ]);

    $this->actingAs($user)
        ->from(route('projects.show', $project))
        ->post(route('projects.invoices.generate', $project), [
            'task_ids' => [$task->id],
        ]);

    expect($task->fresh()->invoice_id)->toBe($existingInvoice->id);
});
