<?php

use App\Models\Invoice;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function mcpCall(string $method, array $params = []): array
{
    return [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => $method,
        'params' => $params,
    ];
}

function mcpData(array $response, string $key): mixed
{
    $text = data_get($response, 'result.content.0.text');

    return data_get(json_decode((string) $text, true), $key);
}

test('mcp mendaftarkan daftar tool icesum', function () {
    $response = $this->postJson('/mcp/icesum', mcpCall('tools/list'));

    $response->assertOk();
    $names = collect($response->json('result.tools'))->pluck('name')->all();

    expect($names)->toContain(
        'list-projects',
        'project-board',
        'list-invoices',
        'invoice-detail',
        'unbilled-summary'
    );
});

test('mcp tool list-projects mengembalikan proyek', function () {
    $project = Project::factory()->create(['name' => 'Proyek MCP']);

    $response = $this->postJson('/mcp/icesum', mcpCall('tools/call', [
        'name' => 'list-projects',
        'arguments' => ['search' => 'MCP'],
    ]));

    $response->assertOk();
    $projects = mcpData($response->json(), 'projects');

    expect($projects)->toHaveCount(1)
        ->and($projects[0]['name'])->toBe('Proyek MCP')
        ->and($projects[0]['id'])->toBe($project->id);
});

test('mcp tool project-board menampilkan grup dan task', function () {
    $project = Project::factory()->create();
    $group = TaskGroup::factory()->create(['project_id' => $project->id, 'name' => 'To Do']);
    Task::factory()->create([
        'project_id' => $project->id,
        'task_group_id' => $group->id,
        'title' => 'Task MCP',
    ]);

    $response = $this->postJson('/mcp/icesum', mcpCall('tools/call', [
        'name' => 'project-board',
        'arguments' => ['project_id' => $project->id],
    ]));

    $response->assertOk();

    expect(mcpData($response->json(), 'name'))->toBe($project->name)
        ->and(mcpData($response->json(), 'groups.0.tasks.0.title'))->toBe('Task MCP');
});

test('mcp tool project-board menolak proyek yang tidak ada', function () {
    $response = $this->postJson('/mcp/icesum', mcpCall('tools/call', [
        'name' => 'project-board',
        'arguments' => ['project_id' => 999999],
    ]));

    $response->assertOk();
    expect($response->json('result.isError'))->toBeTrue();
});

test('mcp tool invoice-detail menampilkan item invoice', function () {
    $invoice = Invoice::factory()->create();

    $response = $this->postJson('/mcp/icesum', mcpCall('tools/call', [
        'name' => 'invoice-detail',
        'arguments' => ['invoice_id' => $invoice->id],
    ]));

    $response->assertOk();

    expect(mcpData($response->json(), 'invoice_number'))->toBe($invoice->invoice_number)
        ->and(mcpData($response->json(), 'items'))->toBeArray();
});

test('mcp tool unbilled-summary meringkas task belum tertagih', function () {
    $project = Project::factory()->create();
    $group = TaskGroup::factory()->create(['project_id' => $project->id]);
    Task::factory()->create([
        'project_id' => $project->id,
        'task_group_id' => $group->id,
        'billable' => true,
        'invoice_id' => null,
    ]);

    $response = $this->postJson('/mcp/icesum', mcpCall('tools/call', [
        'name' => 'unbilled-summary',
        'arguments' => ['project_id' => $project->id],
    ]));

    $response->assertOk();
    expect(mcpData($response->json(), 'tasks'))->toHaveCount(1);
});
