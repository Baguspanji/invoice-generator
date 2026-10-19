<?php

namespace App\Services\Documentation;

use App\Enums\DocumentationPageStatus;
use App\Models\DocumentationPage;
use App\Models\DocumentationSpace;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PageService
{
    public function __construct(
        private ContentValidationService $contentValidation,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(DocumentationSpace $space, array $data, User $user): DocumentationPage
    {
        return DB::transaction(function () use ($space, $data, $user) {
            $parentId = $this->resolveParentId($space, null, $data['parent_id'] ?? null);
            $title = $data['title'];

            $order = $space->pages()
                ->where('parent_id', $parentId)
                ->max('order_column') ?? 0;

            return $space->pages()->create([
                'category_id' => $data['category_id'] ?? null,
                'parent_id' => $parentId,
                'title' => $title,
                'slug' => $this->uniqueSlug($space, $title),
                'content' => $data['content'] ?? ['type' => 'doc', 'content' => []],
                'status' => DocumentationPageStatus::DRAFT,
                'created_by' => $user->id,
                'lock_version' => 1,
                'order_column' => (int) $order + 1,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws StaleContentException
     */
    public function update(DocumentationPage $page, array $data, User $user): DocumentationPage
    {
        if (array_key_exists('lock_version', $data) && (int) $data['lock_version'] !== $page->lock_version) {
            throw new StaleContentException($page->lock_version);
        }

        return DB::transaction(function () use ($page, $data, $user) {
            $attributes = ['updated_by' => $user->id];

            if (array_key_exists('title', $data)) {
                $attributes['title'] = $data['title'];
                $attributes['slug'] = $this->uniqueSlug($page->space, $data['title'], $page->id);
            }

            if (array_key_exists('content', $data)) {
                $this->contentValidation->validate($data['content']);
                $attributes['content'] = $data['content'];
            }

            if (array_key_exists('category_id', $data)) {
                $attributes['category_id'] = $data['category_id'];
            }

            if (array_key_exists('parent_id', $data)) {
                $attributes['parent_id'] = $this->resolveParentId($page->space, $page, $data['parent_id']);
            }

            if (array_key_exists('order_column', $data)) {
                $attributes['order_column'] = (int) $data['order_column'];
            }

            $page->fill($attributes);
            $page->lock_version = $page->lock_version + 1;
            $page->save();

            return $page;
        });
    }

    public function delete(DocumentationPage $page): void
    {
        DB::transaction(function () use ($page) {
            foreach ($page->attachments as $attachment) {
                Storage::disk($attachment->disk)->delete($attachment->file_path);
            }

            $page->delete();
        });
    }

    /**
     * Susun daftar halaman menjadi pohon hierarki.
     *
     * @return list<array<string, mixed>>
     */
    public function tree(DocumentationSpace $space): array
    {
        /** @var Collection<int, DocumentationPage> $pages */
        $pages = $space->pages()->orderBy('order_column')->orderBy('id')->get();

        $byParent = $pages->groupBy(fn (DocumentationPage $page) => $page->parent_id ?? 0);

        return $this->buildBranch($byParent, 0, 0);
    }

    /**
     * @param  Collection<int, Collection<int, DocumentationPage>>  $byParent
     * @return list<array<string, mixed>>
     */
    private function buildBranch(Collection $byParent, int $parentId, int $depth): array
    {
        if ($depth > 20) {
            return [];
        }

        return $byParent->get($parentId, collect())
            ->map(fn (DocumentationPage $page): array => [
                'id' => $page->id,
                'title' => $page->title,
                'status' => [
                    'value' => $page->status->value,
                    'label' => $page->status->label(),
                    'badge_class' => $page->status->badgeClass(),
                ],
                'children' => $this->buildBranch($byParent, $page->id, $depth + 1),
            ])
            ->values()
            ->all();
    }

    private function resolveParentId(DocumentationSpace $space, ?DocumentationPage $page, ?int $parentId): ?int
    {
        if ($parentId === null) {
            return null;
        }

        $parent = $space->pages()->find($parentId);

        if (! $parent) {
            throw ValidationException::withMessages([
                'parent_id' => 'Halaman induk tidak ditemukan pada workspace ini.',
            ]);
        }

        if ($page && $parent->id === $page->id) {
            throw ValidationException::withMessages([
                'parent_id' => 'Halaman tidak dapat menjadi induk bagi dirinya sendiri.',
            ]);
        }

        if ($page) {
            $cursor = $parent;

            while ($cursor) {
                if ($cursor->id === $page->id) {
                    throw ValidationException::withMessages([
                        'parent_id' => 'Hierarki tidak valid: tidak boleh membentuk siklus.',
                    ]);
                }

                $cursor = $cursor->parent_id
                    ? $space->pages()->find($cursor->parent_id)
                    : null;
            }
        }

        return $parent->id;
    }

    private function uniqueSlug(DocumentationSpace $space, string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'halaman';
        $slug = $base;
        $suffix = 2;

        while (
            $space->pages()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
