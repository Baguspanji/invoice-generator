<?php

namespace App\Models;

use App\Enums\DocumentationPageStatus;
use Database\Factories\DocumentationVersionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'page_id',
    'version_number',
    'title',
    'content',
    'status',
    'change_summary',
    'created_by',
])]
class DocumentationVersion extends Model
{
    /** @use HasFactory<DocumentationVersionFactory> */
    use HasFactory;

    /**
     * Snapshot bersifat immutable: jangan pernah memperbarui versi lama.
     */
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'content' => 'array',
            'status' => DocumentationPageStatus::class,
            'version_number' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<DocumentationPage, $this>
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(DocumentationPage::class, 'page_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
