<?php

namespace App\Models;

use Database\Factories\DocumentationSpaceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'project_id',
    'name',
    'description',
    'created_by',
])]
class DocumentationSpace extends Model
{
    /** @use HasFactory<DocumentationSpaceFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<DocumentationCategory, $this>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(DocumentationCategory::class, 'space_id')->orderBy('order_column');
    }

    /**
     * @return HasMany<DocumentationPage, $this>
     */
    public function pages(): HasMany
    {
        return $this->hasMany(DocumentationPage::class, 'space_id');
    }

    /**
     * @return HasMany<DocumentationActivity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(DocumentationActivity::class, 'space_id');
    }
}
