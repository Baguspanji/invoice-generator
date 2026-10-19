<?php

namespace App\Http\Controllers\Documentation;

use App\Http\Controllers\Controller;
use App\Models\DocumentationComment;
use App\Models\DocumentationPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CommentController extends Controller
{
    public function index(Request $request, DocumentationPage $page): JsonResponse
    {
        return response()->json(['comments' => $this->comments($page, $request)]);
    }

    public function store(Request $request, DocumentationPage $page): JsonResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $page->comments()->create([
            'user_id' => $request->user()->id,
            'body' => trim($validated['body']),
        ]);

        return response()->json([
            'success' => true,
            'comments' => $this->comments($page, $request),
        ]);
    }

    public function destroy(Request $request, DocumentationPage $page, DocumentationComment $comment): JsonResponse
    {
        abort_unless((int) $comment->page_id === (int) $page->id, 404);
        abort_unless($comment->user_id === $request->user()->id, 403);

        $comment->delete();

        return response()->json([
            'success' => true,
            'comments' => $this->comments($page, $request),
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function comments(DocumentationPage $page, Request $request): Collection
    {
        return $page->comments()
            ->with('user')
            ->get()
            ->map(fn (DocumentationComment $comment): array => $comment->toPayload($request->user()))
            ->values();
    }
}
