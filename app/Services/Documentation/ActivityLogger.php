<?php

namespace App\Services\Documentation;

use App\Enums\DocumentationActivityAction;
use App\Models\DocumentationActivity;
use App\Models\DocumentationPage;
use App\Models\User;

class ActivityLogger
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function log(
        DocumentationPage $page,
        ?User $user,
        DocumentationActivityAction $action,
        string $description,
        array $properties = [],
    ): DocumentationActivity {
        return DocumentationActivity::create([
            'space_id' => $page->space_id,
            'page_id' => $page->id,
            'user_id' => $user?->id,
            'action' => $action,
            'description' => $description,
            'properties' => $properties === [] ? null : $properties,
        ]);
    }

    /**
     * Catat aktivitas berulang dengan pembatasan agar autosave tidak membanjiri log.
     *
     * @param  array<string, mixed>  $properties
     */
    public function logThrottled(
        DocumentationPage $page,
        ?User $user,
        DocumentationActivityAction $action,
        string $description,
        array $properties = [],
        int $minutes = 15,
    ): ?DocumentationActivity {
        $alreadyLogged = DocumentationActivity::query()
            ->where('page_id', $page->id)
            ->where('user_id', $user?->id)
            ->where('action', $action)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->exists();

        if ($alreadyLogged) {
            return null;
        }

        return $this->log($page, $user, $action, $description, $properties);
    }
}
