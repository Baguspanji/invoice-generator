<?php

namespace App\Mcp\Tools;

use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Summarize billable but not yet invoiced tasks of a project, with logged minutes and estimated amounts.')]
#[IsReadOnly]
class UnbilledSummary extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $request->validate([
            'project_id' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $project = Project::findOrFail($request->get('project_id'));
        } catch (ModelNotFoundException) {
            return Response::error('Project not found.');
        }

        $tasks = $project->tasks()
            ->with('timeLogs')
            ->whereNull('invoice_id')
            ->where('billable', true)
            ->orderBy('order_column')
            ->get()
            ->map(function ($task) use ($project) {
                $minutes = (int) $task->timeLogs->sum('minutes');

                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'fixed_price' => $task->fixed_price !== null ? (float) $task->fixed_price : null,
                    'logged_minutes' => $minutes,
                    'estimated_amount' => $task->fixed_price !== null
                        ? (float) $task->fixed_price
                        : ceil($minutes / 60) * (float) $project->rate,
                ];
            })
            ->values()
            ->all();

        return Response::json([
            'project_id' => $project->id,
            'project_name' => $project->name,
            'pricing_type' => $project->pricing_type,
            'rate' => (float) $project->rate,
            'tasks' => $tasks,
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'project_id' => $schema->integer()->description('ID of the project')->required()->min(1),
        ];
    }
}
