<?php

namespace App\Mcp\Tools;

use App\Models\Project;
use App\Services\ProjectInvoiceService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\JsonSchema\Types\Type;
use InvalidArgumentException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Generate an invoice from a project billable tasks. Use unbilled-summary first to pick task IDs.')]
class GenerateInvoice extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request, ProjectInvoiceService $service): Response
    {
        $request->validate([
            'project_id' => ['required', 'integer', 'min:1'],
            'task_ids' => ['required', 'array', 'min:1'],
            'task_ids.*' => ['integer', 'min:1'],
            'due_date' => ['sometimes', 'nullable', 'date'],
        ]);

        try {
            $project = Project::findOrFail($request->get('project_id'));
        } catch (ModelNotFoundException) {
            return Response::error('Project not found.');
        }

        try {
            $invoice = $service->createFromProject(
                $project,
                $request->get('task_ids'),
                $request->get('due_date')
            );
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage());
        }

        return Response::json([
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'customer_id' => $invoice->customer_id,
            'due_date' => $invoice->due_date?->toDateString(),
            'total_amount' => (float) $invoice->total_amount,
            'status' => $invoice->status->value,
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
            'task_ids' => $schema->array()->description('Billable task IDs to invoice')->required()->items($schema->integer())->min(1),
            'due_date' => $schema->string()->description('Payment due date (YYYY-MM-DD, default 14 days)'),
        ];
    }
}
