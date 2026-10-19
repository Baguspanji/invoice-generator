<?php

namespace App\Http\Controllers\Documentation;

use App\Enums\DocumentationActivityAction;
use App\Http\Controllers\Controller;
use App\Models\DocumentationAttachment;
use App\Models\DocumentationPage;
use App\Services\Documentation\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function store(Request $request, DocumentationPage $page, ActivityLogger $activity): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $file = $request->file('file');
        $path = $file->store('documentation-attachments', 'public');

        $attachment = $page->attachments()->create([
            'uploaded_by' => $request->user()->id,
            'disk' => 'public',
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getClientMimeType(),
        ]);

        $activity->log(
            $page,
            $request->user(),
            DocumentationActivityAction::ATTACHMENT_ADDED,
            'Menambahkan lampiran «'.$attachment->file_name.'»',
            ['attachment_id' => $attachment->id],
        );

        return response()->json([
            'success' => true,
            'attachment' => [
                'id' => $attachment->id,
                'file_name' => $attachment->file_name,
                'file_size' => $attachment->file_size,
                'human_size' => $attachment->humanSize(),
                'mime_type' => $attachment->mime_type,
                'url' => $attachment->url(),
            ],
        ]);
    }

    public function destroy(Request $request, DocumentationPage $page, DocumentationAttachment $attachment, ActivityLogger $activity): JsonResponse
    {
        abort_unless((int) $attachment->page_id === (int) $page->id, 404);

        Storage::disk($attachment->disk)->delete($attachment->file_path);

        $activity->log(
            $page,
            $request->user(),
            DocumentationActivityAction::ATTACHMENT_REMOVED,
            'Menghapus lampiran «'.$attachment->file_name.'»',
            ['attachment_id' => $attachment->id],
        );

        $attachment->delete();

        return response()->json(['success' => true]);
    }
}
