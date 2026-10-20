<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpActor;
use App\Models\DocumentationPage;
use App\Services\Documentation\PublishingService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Move a documentation page through the publishing workflow: submit_review, request_changes or publish (publish creates a version snapshot).')]
class ChangeDocPageStatus extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request, PublishingService $publishing): Response
    {
        $request->validate([
            'page_id' => ['required', 'integer', 'min:1'],
            'action' => ['required', 'in:submit_review,request_changes,publish'],
            'change_summary' => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        try {
            $page = DocumentationPage::findOrFail($request->get('page_id'));
        } catch (ModelNotFoundException) {
            return Response::error('Documentation page not found.');
        }

        $actor = McpActor::resolve();

        if (! $actor) {
            return Response::error('No staff user available to attribute this change to.');
        }

        $page = match ($request->get('action')) {
            'submit_review' => $publishing->submitForReview($page, $actor),
            'request_changes' => $publishing->requestChanges($page, $actor),
            default => $publishing->publish($page, $actor, $request->get('change_summary')),
        };

        return Response::json([
            'id' => $page->id,
            'title' => $page->title,
            'status' => $page->status->value,
            'published_at' => $page->published_at?->toISOString(),
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
            'page_id' => $schema->integer()->description('ID of the documentation page')->required()->min(1),
            'action' => $schema->string()->description('One of: submit_review, request_changes, publish')->required(),
            'change_summary' => $schema->string()->description('Version note (publish only)')->max(500),
        ];
    }
}
