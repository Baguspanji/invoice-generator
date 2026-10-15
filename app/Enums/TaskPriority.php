<?php

namespace App\Enums;

enum TaskPriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case URGENT = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::LOW => 'Low',
            self::MEDIUM => 'Medium',
            self::HIGH => 'High',
            self::URGENT => 'Urgent',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::LOW => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
            self::MEDIUM => 'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400',
            self::HIGH => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400',
            self::URGENT => 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400',
        };
    }
}
