<?php

namespace App\Services\Documentation;

use App\Enums\DocumentationPageStatus;
use App\Models\DocumentationPage;
use App\Models\DocumentationVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class VersionService
{
    public function snapshot(DocumentationPage $page, User $user, ?string $changeSummary = null): DocumentationVersion
    {
        $next = (int) $page->versions()->max('version_number') + 1;

        return $page->versions()->create([
            'version_number' => $next,
            'title' => $page->title,
            'content' => $page->content,
            'status' => $page->status,
            'change_summary' => $changeSummary,
            'created_by' => $user->id,
        ]);
    }

    /**
     * Pulihkan versi lama sebagai revisi baru (bukan menimpa versi manapun).
     */
    public function restore(DocumentationPage $page, DocumentationVersion $version, User $user): DocumentationPage
    {
        return DB::transaction(function () use ($page, $version, $user) {
            $page->fill([
                'title' => $version->title,
                'content' => $version->content,
                'status' => DocumentationPageStatus::DRAFT,
                'updated_by' => $user->id,
            ]);
            $page->lock_version = $page->lock_version + 1;
            $page->save();

            $this->snapshot($page, $user, 'Restore dari versi #'.$version->version_number);

            return $page;
        });
    }
}
