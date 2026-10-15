<?php

namespace App\Models;

use Database\Factories\TaskAttachmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'task_id',
    'file_name',
    'file_path',
    'file_size',
    'mime_type',
])]
class TaskAttachment extends Model
{
    /** @use HasFactory<TaskAttachmentFactory> */
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
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->file_path);
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
