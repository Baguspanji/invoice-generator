<?php

namespace App\Enums;

enum DocumentationActivityAction: string
{
    case CREATED = 'created';
    case UPDATED = 'updated';
    case PUBLISHED = 'published';
    case SUBMITTED_FOR_REVIEW = 'submitted_for_review';
    case CHANGES_REQUESTED = 'changes_requested';
    case RESTORED = 'restored';
    case DELETED = 'deleted';
    case TASK_LINKED = 'task_linked';
    case TASK_UNLINKED = 'task_unlinked';
    case ATTACHMENT_ADDED = 'attachment_added';
    case ATTACHMENT_REMOVED = 'attachment_removed';

    public function label(): string
    {
        return match ($this) {
            self::CREATED => 'Dibuat',
            self::UPDATED => 'Diperbarui',
            self::PUBLISHED => 'Dipublikasikan',
            self::SUBMITTED_FOR_REVIEW => 'Dikirim untuk review',
            self::CHANGES_REQUESTED => 'Diminta revisi',
            self::RESTORED => 'Versi dipulihkan',
            self::DELETED => 'Dihapus',
            self::TASK_LINKED => 'Task ditautkan',
            self::TASK_UNLINKED => 'Tautan task dilepas',
            self::ATTACHMENT_ADDED => 'Lampiran ditambahkan',
            self::ATTACHMENT_REMOVED => 'Lampiran dihapus',
        };
    }
}
