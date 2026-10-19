<?php

namespace App\Models;

use App\Enums\DocumentationActivityAction;
use Database\Factories\DocumentationActivityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'space_id',
    'page_id',
    'user_id',
    'action',
    'description',
    'properties',
])]
class DocumentationActivity extends Model
{
    /** @use HasFactory<DocumentationActivityFactory> */
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'documentation_activity_logs';

    /**
     * Log aktivitas bersifat append-only.
     */
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => DocumentationActivityAction::class,
            'properties' => 'array',
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
     * @return BelongsTo<DocumentationPage, $this>
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(DocumentationPage::class, 'page_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action->value,
            'label' => $this->action->label(),
            'description' => $this->description,
            'properties' => $this->properties,
            'user_name' => $this->user?->name,
            'created_at' => $this->created_at?->toISOString(),
            'created_at_label' => $this->created_at?->translatedFormat('d M Y H:i'),
        ];
    }
}
