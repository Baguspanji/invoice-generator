export function statusBadgeClass(value) {
    switch (value) {
        case 'draft':
            return 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400';
        case 'in_review':
            return 'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400';
        case 'changes_requested':
            return 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400';
        case 'published':
            return 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400';
        default:
            return 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400';
    }
}

export function statusDotClass(value) {
    switch (value) {
        case 'published':
            return 'bg-emerald-500';
        case 'in_review':
            return 'bg-blue-500';
        case 'changes_requested':
            return 'bg-amber-500';
        default:
            return 'bg-slate-400';
    }
}

export function statusLabel(value) {
    switch (value) {
        case 'draft':
            return 'Draft';
        case 'in_review':
            return 'In Review';
        case 'changes_requested':
            return 'Changes Requested';
        case 'published':
            return 'Published';
        default:
            return value;
    }
}

export function activityDotClass(action) {
    switch (action) {
        case 'published':
            return 'bg-emerald-500';
        case 'submitted_for_review':
            return 'bg-blue-500';
        case 'changes_requested':
            return 'bg-amber-500';
        case 'deleted':
            return 'bg-red-500';
        case 'restored':
            return 'bg-violet-500';
        case 'created':
            return 'bg-primary';
        default:
            return 'bg-slate-400';
    }
}

export function priorityBadgeClass(priority) {
    const value = typeof priority === 'object' && priority !== null ? priority.value : priority;

    switch (value) {
        case 'low':
            return 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400';
        case 'high':
            return 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400';
        case 'urgent':
            return 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400';
        case 'medium':
        default:
            return 'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400';
    }
}
