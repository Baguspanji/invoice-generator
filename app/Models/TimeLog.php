<?php

namespace App\Models;

use Database\Factories\TimeLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'task_id',
    'user_id',
    'minutes',
    'timer_start',
    'timer_stop',
])]
class TimeLog extends Model
{
    /** @use HasFactory<TimeLogFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'minutes' => 'integer',
            'timer_start' => 'datetime',
            'timer_stop' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isRunning(): bool
    {
        return $this->timer_start !== null && $this->timer_stop === null;
    }

    public function stop(): void
    {
        if (! $this->isRunning()) {
            return;
        }

        $now = now();
        $minutes = max(1, (int) round($this->timer_start->diffInMinutes($now)));

        $this->update([
            'timer_stop' => $now,
            'minutes' => $minutes,
        ]);
    }
}
