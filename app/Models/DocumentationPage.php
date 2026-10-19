<?php

namespace App\Models;

use App\Enums\DocumentationPageStatus;
use Database\Factories\DocumentationPageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'space_id',
    'category_id',
    'parent_id',
    'title',
    'slug',
    'content',
    'status',
    'created_by',
    'updated_by',
    'published_at',
    'lock_version',
    'order_column',
])]
class DocumentationPage extends Model
{
    /** @use HasFactory<DocumentationPageFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'content' => 'array',
            'status' => DocumentationPageStatus::class,
            'published_at' => 'datetime',
            'lock_version' => 'integer',
            'order_column' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<DocumentationSpace, $this>
     */
    public function space(): BelongsTo
    {
        return $this->belongsTo(DocumentationSpace::class, 'space_id');
    }

    /**
     * @return BelongsTo<DocumentationCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentationCategory::class, 'category_id');
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<self, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('order_column')->orderBy('id');
    }

    /**
     * @return HasMany<DocumentationVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(DocumentationVersion::class, 'page_id')->latest('version_number');
    }

    /**
     * @return BelongsToMany<Task, $this>
     */
    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'documentation_page_task', 'page_id', 'task_id')
            ->withPivot('created_by');
    }

    /**
     * @return HasMany<DocumentationAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(DocumentationAttachment::class, 'page_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @return HasMany<DocumentationActivity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(DocumentationActivity::class, 'page_id')->latest('id');
    }

    public function isPublished(): bool
    {
        return $this->status === DocumentationPageStatus::PUBLISHED;
    }

    /**
     * @param  Builder<DocumentationPage>  $query
     * @return Builder<DocumentationPage>
     */
    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * @param  Builder<DocumentationPage>  $query
     * @return Builder<DocumentationPage>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', DocumentationPageStatus::PUBLISHED);
    }
}
