<?php

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\McpToken;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskGroup;
use App\Models\User;
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

function mcpAuthHeaders(): array
{
    [, $plaintext] = McpToken::createToken('test-client');

    return ['Authorization' => 'Bearer '.$plaintext];
}

function mcpPost(array $payload)
{
    return test()->postJson('/mcp/icesum', $payload, mcpAuthHeaders());
}

function mcpData(array $response, string $key): mixed
{
    $text = data_get($response, 'result.content.0.text');

    return data_get(json_decode((string) $text, true), $key);
}

test('mcp menolak tanpa token dan token salah', function () {
    $this->postJson('/mcp/icesum', mcpCall('tools/list'))->assertUnauthorized();

    $this->postJson('/mcp/icesum', mcpCall('tools/list'), ['Authorization' => 'Bearer salah'])
        ->assertUnauthorized();
});

test('mcp menolak token kedaluwarsa', function () {
    [, $plaintext] = McpToken::createToken('expired', now()->subDay());

    $this->postJson('/mcp/icesum', mcpCall('tools/list'), ['Authorization' => 'Bearer '.$plaintext])
        ->assertUnauthorized();
});

test('mcp mendaftarkan daftar tool icesum', function () {
    $response = mcpPost(mcpCall('tools/list'));

    $response->assertOk();
    $names = collect($response->json('result.tools'))->pluck('name')->all();

    expect($names)->toContain(
        'list-projects',
        'project-board',
        'create-task',
        'update-task',
        'list-invoices',
        'invoice-detail',
        'unbilled-summary',
        'generate-invoice',
        'mark-invoice-paid',
        'cancel-invoice',
        'download-invoice-pdf'
    );
});

test('mcp mendaftarkan resource invoice pdf', function () {
    $response = mcpPost(mcpCall('resources/templates/list'));

    $response->assertOk();
    $templates = $response->json('result.resourceTemplates');

    expect($templates[0]['uriTemplate'])->toBe('invoice://{invoice_id}/pdf')
        ->and($templates[0]['mimeType'])->toBe('application/pdf');
});

test('mcp tool list-projects mengembalikan proyek', function () {
    $project = Project::factory()->create(['name' => 'Proyek MCP']);

    $response = mcpPost(mcpCall('tools/call', [
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

    $response = mcpPost(mcpCall('tools/call', [
        'name' => 'project-board',
        'arguments' => ['project_id' => $project->id],
    ]));

    $response->assertOk();

    expect(mcpData($response->json(), 'name'))->toBe($project->name)
        ->and(mcpData($response->json(), 'groups.0.tasks.0.title'))->toBe('Task MCP');
});

test('mcp tool project-board menolak proyek yang tidak ada', function () {
    $response = mcpPost(mcpCall('tools/call', [
        'name' => 'project-board',
        'arguments' => ['project_id' => 999999],
    ]));

    $response->assertOk();
    expect($response->json('result.isError'))->toBeTrue();
});

test('mcp tool invoice-detail menampilkan item invoice', function () {
    $invoice = Invoice::factory()->create();

    $response = mcpPost(mcpCall('tools/call', [
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

    $response = mcpPost(mcpCall('tools/call', [
        'name' => 'unbilled-summary',
        'arguments' => ['project_id' => $project->id],
    ]));

    $response->assertOk();
    expect(mcpData($response->json(), 'tasks'))->toHaveCount(1);
});

test('mcp tool create-task membuat task baru', function () {
    $project = Project::factory()->create();
    $group = TaskGroup::factory()->create(['project_id' => $project->id]);

    $response = mcpPost(mcpCall('tools/call', [
        'name' => 'create-task',
        'arguments' => [
            'project_id' => $project->id,
            'task_group_id' => $group->id,
            'title' => 'Task via MCP',
            'priority' => 'high',
        ],
    ]));

    $response->assertOk();

    expect(mcpData($response->json(), 'title'))->toBe('Task via MCP');
    expect(Task::where('title', 'Task via MCP')->exists())->toBeTrue();
});

test('mcp tool create-task menolak grup dari proyek lain', function () {
    $project = Project::factory()->create();
    $otherGroup = TaskGroup::factory()->create();

    $response = mcpPost(mcpCall('tools/call', [
        'name' => 'create-task',
        'arguments' => [
            'project_id' => $project->id,
            'task_group_id' => $otherGroup->id,
            'title' => 'Task lintas proyek',
        ],
    ]));

    $response->assertOk();
    expect($response->json('result.isError'))->toBeTrue();
    expect(Task::where('title', 'Task lintas proyek')->exists())->toBeFalse();
});

test('mcp tool update-task mengubah sebagian field', function () {
    $project = Project::factory()->create();
    $group = TaskGroup::factory()->create(['project_id' => $project->id]);
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'task_group_id' => $group->id,
        'title' => 'Judul lama',
    ]);

    $response = mcpPost(mcpCall('tools/call', [
        'name' => 'update-task',
        'arguments' => [
            'task_id' => $task->id,
            'title' => 'Judul baru',
            'completed' => true,
        ],
    ]));

    $response->assertOk();

    expect(mcpData($response->json(), 'title'))->toBe('Judul baru')
        ->and(mcpData($response->json(), 'completed'))->toBeTrue();
    expect($task->fresh()->completed_at)->not->toBeNull();
});

test('mcp tool generate-invoice membuat invoice dari task', function () {
    $project = Project::factory()->create();
    $group = TaskGroup::factory()->create(['project_id' => $project->id]);
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'task_group_id' => $group->id,
        'billable' => true,
        'invoice_id' => null,
    ]);

    $response = mcpPost(mcpCall('tools/call', [
        'name' => 'generate-invoice',
        'arguments' => [
            'project_id' => $project->id,
            'task_ids' => [$task->id],
        ],
    ]));

    $response->assertOk();

    expect(mcpData($response->json(), 'status'))->toBe('UNPAID');
    expect($task->fresh()->invoice_id)->not->toBeNull();
});

test('mcp tool mark-invoice-paid melunasi invoice unpaid', function () {
    $invoice = Invoice::factory()->unpaid()->create();

    $response = mcpPost(mcpCall('tools/call', [
        'name' => 'mark-invoice-paid',
        'arguments' => ['invoice_id' => $invoice->id],
    ]));

    $response->assertOk();

    expect(mcpData($response->json(), 'status'))->toBe('PAID');
    expect($invoice->fresh()->status->value)->toBe('PAID');
});

test('mcp tool mark-invoice-paid menolak invoice yang sudah lunas', function () {
    $invoice = Invoice::factory()->paid()->create();

    $response = mcpPost(mcpCall('tools/call', [
        'name' => 'mark-invoice-paid',
        'arguments' => ['invoice_id' => $invoice->id],
    ]));

    $response->assertOk();
    expect($response->json('result.isError'))->toBeTrue();
});

test('mcp tool cancel-invoice membatalkan invoice unpaid', function () {
    $invoice = Invoice::factory()->unpaid()->create();

    $response = mcpPost(mcpCall('tools/call', [
        'name' => 'cancel-invoice',
        'arguments' => ['invoice_id' => $invoice->id],
    ]));

    $response->assertOk();

    expect(mcpData($response->json(), 'status'))->toBe('CANCELLED');
    expect($invoice->fresh()->status->value)->toBe('CANCELLED');
});

test('mcp tool download-invoice-pdf mengembalikan metadata dan resource link', function () {
    $invoice = Invoice::factory()->create();

    $response = mcpPost(mcpCall('tools/call', [
        'name' => 'download-invoice-pdf',
        'arguments' => ['invoice_id' => $invoice->id],
    ]));

    $response->assertOk();
    expect($response->json('result.isError'))->toBeFalse();

    $meta = mcpData($response->json(), 'invoice_number');

    expect($meta)->toBe($invoice->invoice_number)
        ->and(mcpData($response->json(), 'filename'))->toEndWith('.pdf')
        ->and(mcpData($response->json(), 'mime_type'))->toBe('application/pdf')
        ->and(mcpData($response->json(), 'resource_uri'))->toBe("invoice://{$invoice->id}/pdf")
        ->and(mcpData($response->json(), 'size_bytes'))->toBeGreaterThan(1000);

    $contents = $response->json('result.content');
    $link = collect($contents)->firstWhere('type', 'resource_link');

    expect($link)->not->toBeNull()
        ->and($link['uri'])->toBe("invoice://{$invoice->id}/pdf")
        ->and($link['mimeType'])->toBe('application/pdf');
});

test('mcp tool download-invoice-pdf menolak invoice yang tidak ada', function () {
    $response = mcpPost(mcpCall('tools/call', [
        'name' => 'download-invoice-pdf',
        'arguments' => ['invoice_id' => 999999],
    ]));

    $response->assertOk();
    expect($response->json('result.isError'))->toBeTrue();
});

test('mcp resource invoice pdf mengembalikan blob pdf', function () {
    $invoice = Invoice::factory()->create();

    $response = mcpPost(mcpCall('resources/read', [
        'uri' => "invoice://{$invoice->id}/pdf",
    ]));

    $response->assertOk();

    $contents = $response->json('result.contents');
    expect($contents)->toHaveCount(1)
        ->and($contents[0]['mimeType'])->toBe('application/pdf')
        ->and($contents[0]['uri'])->toBe("invoice://{$invoice->id}/pdf");

    $binary = base64_decode($contents[0]['blob'], true);
    expect($binary)->not->toBeFalse()
        ->and(substr((string) $binary, 0, 5))->toBe('%PDF-');
});

test('mcp resource invoice pdf menolak invoice yang tidak ada', function () {
    $response = mcpPost(mcpCall('resources/read', [
        'uri' => 'invoice://999999/pdf',
    ]));

    $response->assertOk();
    // Resource tidak ketemu atau handle mengembalikan error.
    expect(
        $response->json('error') !== null || $response->json('result.contents') === null
        || ($response->json('result.content.0.text') !== null)
    )->toBeTrue();
});

test('staff dapat generate dan mencabut token mcp dari pengaturan', function () {
    $staff = User::factory()->create(['role' => UserRole::STAFF->value]);

    $response = $this->actingAs($staff)->post(route('settings.mcp-tokens.store'), [
        'name' => 'Claude Desktop',
        'expires_in' => 'never',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('mcp_token_plaintext');

    $token = McpToken::where('name', 'Claude Desktop')->first();
    expect($token)->not->toBeNull();
    expect($token->token)->toBe(hash('sha256', session('mcp_token_plaintext')));

    $this->actingAs($staff)
        ->delete(route('settings.mcp-tokens.destroy', $token))
        ->assertRedirect();

    expect(McpToken::find($token->id))->toBeNull();
});

test('tamu dan customer tidak dapat mengelola token mcp', function () {
    $this->post(route('settings.mcp-tokens.store'), ['name' => 'X'])->assertRedirect('/login');

    $customer = User::factory()->create([
        'role' => UserRole::CUSTOMER->value,
        'customer_id' => Customer::factory()->create()->id,
    ]);

    $this->actingAs($customer)
        ->post(route('settings.mcp-tokens.store'), ['name' => 'X'])
        ->assertForbidden();
});

test('mcp tool create-task membuat subtask di bawah parent', function () {
    $project = Project::factory()->create();
    $group = TaskGroup::factory()->create(['project_id' => $project->id]);
    $parent = Task::factory()->create(['project_id' => $project->id, 'task_group_id' => $group->id]);

    $response = mcpPost(mcpCall('tools/call', [
        'name' => 'create-task',
        'arguments' => [
            'project_id' => $project->id,
            'parent_id' => $parent->id,
            'title' => 'Subtask via MCP',
        ],
    ]));

    $response->assertOk();

    $subtask = Task::where('title', 'Subtask via MCP')->first();
    expect($subtask->parent_id)->toBe($parent->id)
        ->and($subtask->task_group_id)->toBe($group->id);
});

test('mcp tool project-board menampilkan subtask', function () {
    $project = Project::factory()->create();
    $group = TaskGroup::factory()->create(['project_id' => $project->id]);
    $parent = Task::factory()->create(['project_id' => $project->id, 'task_group_id' => $group->id]);
    Task::factory()->subtaskOf($parent)->create(['title' => 'Subtask MCP']);

    $response = mcpPost(mcpCall('tools/call', [
        'name' => 'project-board',
        'arguments' => ['project_id' => $project->id],
    ]));

    $response->assertOk();

    expect(mcpData($response->json(), 'groups.0.tasks'))->toHaveCount(1)
        ->and(mcpData($response->json(), 'groups.0.tasks.0.subtasks.0.title'))->toBe('Subtask MCP');
});

test('mcp tool update-task menolak penyelesaian task yang masih punya subtask terbuka', function () {
    $project = Project::factory()->create();
    $group = TaskGroup::factory()->create(['project_id' => $project->id]);
    $parent = Task::factory()->create(['project_id' => $project->id, 'task_group_id' => $group->id]);
    Task::factory()->subtaskOf($parent)->create();

    $response = mcpPost(mcpCall('tools/call', [
        'name' => 'update-task',
        'arguments' => ['task_id' => $parent->id, 'completed' => true],
    ]));

    $response->assertOk();

    expect($parent->fresh()->isCompleted())->toBeFalse();
});
