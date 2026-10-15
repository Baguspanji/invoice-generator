<?php

namespace App\Mcp\Tools;

use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List projects with customer, pricing, task counts and completion progress.')]
#[IsReadOnly]
class ListProjects extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $request->validate([
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'customer_id' => ['sometimes', 'nullable', 'integer'],
            'limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $projects = Project::with(['customer'])
            ->withCount([
                'tasks as all_tasks_count',
                'tasks as completed_tasks_count' => fn ($query) => $query->whereNotNull('completed_at'),
            ])
            ->when($request->get('search'), fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->when($request->get('customer_id'), fn ($query, $customerId) => $query->where('customer_id', $customerId))
            ->latest()
            ->limit((int) ($request->get('limit') ?? 20))
            ->get()
            ->map(fn (Project $project) => [
                'id' => $project->id,
                'name' => $project->name,
                'customer' => $project->customer?->name,
                'pricing_type' => $project->pricing_type,
                'rate' => (float) $project->rate,
                'all_tasks' => $project->all_tasks_count,
                'completed_tasks' => $project->completed_tasks_count,
            ])
            ->values()
            ->all();

        return Response::json(['projects' => $projects]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Filter by project name')->max(255),
            'customer_id' => $schema->integer()->description('Filter by customer ID')->min(1),
            'limit' => $schema->integer()->description('Max projects to return')->min(1)->max(50),
        ];
    }
}
