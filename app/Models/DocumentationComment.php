<?php

namespace App\Models;

use Database\Factories\DocumentationCommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'page_id',
    'user_id',
    'body',
])]
class DocumentationComment extends Model
{
    /** @use HasFactory<DocumentationCommentFactory> */
    use HasFactory;

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
    public function toPayload(?User $viewer = null): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'user_id' => $this->user_id,
            'user_name' => $this->user?->name,
            'created_at' => $this->created_at?->toISOString(),
            'created_at_label' => $this->created_at?->translatedFormat('d M Y H:i'),
            'can_delete' => $viewer !== null && $viewer->id === $this->user_id,
        ];
    }
}
