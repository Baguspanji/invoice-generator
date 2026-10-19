<?php

namespace App\Models;

use Database\Factories\DocumentationCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'space_id',
    'name',
    'slug',
    'order_column',
])]
class DocumentationCategory extends Model
{
    /** @use HasFactory<DocumentationCategoryFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
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
     * @return HasMany<DocumentationPage, $this>
     */
    public function pages(): HasMany
    {
        return $this->hasMany(DocumentationPage::class, 'category_id');
    }
}
