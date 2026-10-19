<?php

namespace App\Services\Documentation;

use App\Enums\DocumentationPageStatus;
use App\Models\DocumentationPage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PublishingService
{
    public function __construct(
        private VersionService $versions,
    ) {}

    public function publish(DocumentationPage $page, User $user, ?string $changeSummary = null): DocumentationPage
    {
        return DB::transaction(function () use ($page, $user, $changeSummary) {
            $this->versions->snapshot($page, $user, $changeSummary ?? 'Dipublikasikan');

            $page->update([
                'status' => DocumentationPageStatus::PUBLISHED,
                'published_at' => now(),
                'updated_by' => $user->id,
            ]);

            return $page;
        });
    }

    public function submitForReview(DocumentationPage $page, User $user): DocumentationPage
    {
        $page->update([
            'status' => DocumentationPageStatus::IN_REVIEW,
            'updated_by' => $user->id,
        ]);

        return $page;
    }

    public function requestChanges(DocumentationPage $page, User $user): DocumentationPage
    {
        $page->update([
            'status' => DocumentationPageStatus::CHANGES_REQUESTED,
            'updated_by' => $user->id,
        ]);

        return $page;
    }
}
