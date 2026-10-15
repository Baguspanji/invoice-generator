<?php

namespace App\Models;

use App\Enums\TaskPriority;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'project_id',
    'task_group_id',
    'assigned_to_user_id',
    'invoice_id',
    'title',
    'priority',
    'description',
    'estimation',
    'fixed_price',
    'billable',
    'order_column',
    'completed_at',
])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'priority' => TaskPriority::class,
            'estimation' => 'decimal:2',
            'fixed_price' => 'decimal:2',
            'billable' => 'boolean',
            'order_column' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<TaskGroup, $this>
     */
    public function taskGroup(): BelongsTo
    {
        return $this->belongsTo(TaskGroup::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return HasMany<TimeLog, $this>
     */
    public function timeLogs(): HasMany
    {
        return $this->hasMany(TimeLog::class);
    }

    /**
     * @return BelongsToMany<Label, $this>
     */
    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class);
    }

    /**
     * @return HasMany<TaskAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class);
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    public function totalLoggedMinutes(): int
    {
        return (int) $this->timeLogs()->sum('minutes');
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->whereNotNull('completed_at');
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('completed_at');
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopeUnbilled(Builder $query): Builder
    {
        return $query->whereNull('invoice_id')->where('billable', true);
    }
}
