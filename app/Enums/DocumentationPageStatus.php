<?php

namespace App\Enums;

enum DocumentationPageStatus: string
{
    case DRAFT = 'draft';
    case IN_REVIEW = 'in_review';
    case CHANGES_REQUESTED = 'changes_requested';
    case PUBLISHED = 'published';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::IN_REVIEW => 'In Review',
            self::CHANGES_REQUESTED => 'Changes Requested',
            self::PUBLISHED => 'Published',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::DRAFT => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
            self::IN_REVIEW => 'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400',
            self::CHANGES_REQUESTED => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400',
            self::PUBLISHED => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400',
        };
    }
}
