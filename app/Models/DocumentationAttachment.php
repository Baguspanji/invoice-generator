<?php

namespace App\Models;

use Database\Factories\DocumentationAttachmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'page_id',
    'uploaded_by',
    'disk',
    'file_path',
    'file_name',
    'file_size',
    'mime_type',
])]
class DocumentationAttachment extends Model
{
    /** @use HasFactory<DocumentationAttachmentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $appends = [
        'url',
        'human_size',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
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
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->file_path);
    }

    public function humanSize(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes >= 1024 && $i < 3; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 1).' '.$units[$i];
    }

    public function getUrlAttribute(): string
    {
        return $this->url();
    }

    public function getHumanSizeAttribute(): string
    {
        return $this->humanSize();
    }
}
