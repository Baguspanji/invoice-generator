<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\MarkdownToDoc;
use App\Mcp\Support\McpActor;
use App\Models\DocumentationPage;
use App\Services\Documentation\PageService;
use App\Services\Documentation\StaleContentException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Update a documentation page title and/or content (Markdown). Requires lock_version from doc-page-detail; fails if the page changed meanwhile.')]
class UpdateDocPage extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request, PageService $pages): Response
    {
        $request->validate([
            'page_id' => ['required', 'integer', 'min:1'],
            'lock_version' => ['required', 'integer', 'min:1'],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'content_markdown' => ['sometimes', 'nullable', 'string'],
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

        $data = ['lock_version' => $request->get('lock_version')];

        if ($request->get('title') !== null) {
            $data['title'] = $request->get('title');
        }

        if ($request->get('content_markdown') !== null) {
            $data['content'] = MarkdownToDoc::convert($request->get('content_markdown'));
        }

        try {
            $page = $pages->update($page, $data, $actor);
        } catch (StaleContentException $exception) {
            return Response::error($exception->getMessage().' Current lock_version: '.$exception->currentLockVersion.'.');
        } catch (ValidationException $exception) {
            return Response::error(collect($exception->errors())->flatten()->implode(' '));
        }

        return Response::json([
            'id' => $page->id,
            'title' => $page->title,
            'status' => $page->status->value,
            'lock_version' => $page->lock_version,
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
            'lock_version' => $schema->integer()->description('Current lock_version of the page')->required()->min(1),
            'title' => $schema->string()->description('New title')->max(255),
            'content_markdown' => $schema->string()->description('New content in Markdown (replaces existing content)'),
        ];
    }
}
