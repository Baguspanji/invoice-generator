<?php

namespace App\Http\Controllers\Documentation;

use App\Http\Controllers\Controller;
use App\Models\DocumentationPage;
use App\Models\DocumentationVersion;
use App\Services\Documentation\VersionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VersionController extends Controller
{
    public function index(DocumentationPage $page): JsonResponse
    {
        $versions = $page->versions()
            ->with('creator')
            ->get()
            ->map(fn (DocumentationVersion $version): array => $this->payload($version));

        return response()->json(['versions' => $versions]);
    }

    public function show(DocumentationPage $page, DocumentationVersion $version): JsonResponse
    {
        abort_unless((int) $version->page_id === (int) $page->id, 404);

        return response()->json(['version' => $this->payload($version, withContent: true)]);
    }

    public function restore(Request $request, DocumentationPage $page, DocumentationVersion $version, VersionService $versionService): JsonResponse
    {
        abort_unless((int) $version->page_id === (int) $page->id, 404);

        $page = $versionService->restore($page, $version, $request->user());

        return response()->json([
            'success' => true,
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
                'content' => $page->content,
                'lock_version' => $page->lock_version,
                'status' => [
                    'value' => $page->status->value,
                    'label' => $page->status->label(),
                    'badge_class' => $page->status->badgeClass(),
                ],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(DocumentationVersion $version, bool $withContent = false): array
    {
        $payload = [
            'id' => $version->id,
            'version_number' => $version->version_number,
            'title' => $version->title,
            'change_summary' => $version->change_summary,
            'status' => $version->status->label(),
            'created_at' => $version->created_at?->toISOString(),
            'created_at_label' => $version->created_at?->translatedFormat('d M Y H:i'),
            'creator_name' => $version->creator?->name,
        ];

        if ($withContent) {
            $payload['content'] = $version->content;
        }

        return $payload;
    }
}
