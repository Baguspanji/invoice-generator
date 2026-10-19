<?php

namespace App\Http\Controllers\Documentation;

use App\Http\Controllers\Controller;
use App\Models\DocumentationActivity;
use App\Models\DocumentationPage;
use Illuminate\Http\JsonResponse;

class ActivityController extends Controller
{
    public function index(DocumentationPage $page): JsonResponse
    {
        $activities = $page->activities()
            ->with('user')
            ->limit(50)
            ->get()
            ->map(fn (DocumentationActivity $activity): array => $activity->toPayload())
            ->values();

        return response()->json(['activities' => $activities]);
    }
}
