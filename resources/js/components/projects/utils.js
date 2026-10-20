export function formatRupiah(amount) {
    if (!amount) return 'Rp 0';
    return 'Rp ' + Number(amount).toLocaleString('id-ID');
}

export function priorityLabel(priority) {
    const val = typeof priority === 'object' && priority !== null ? priority.value : priority;
    switch (val) {
        case 'low': return 'Low';
        case 'medium': return 'Medium';
        case 'high': return 'High';
        case 'urgent': return 'Urgent';
        default: return 'Medium';
    }
}

export function priorityBadgeClass(priority) {
    const val = typeof priority === 'object' && priority !== null ? priority.value : priority;
    switch (val) {
        case 'low': return 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400';
        case 'medium': return 'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400';
        case 'high': return 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400';
        case 'urgent': return 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400';
        default: return 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400';
    }
}

export function isTaskCompleted(task) {
    return Boolean(task.completed_at);
}

export function subtaskProgress(task) {
    const subtasks = task.subtasks || [];
    return {
        done: subtasks.filter(isTaskCompleted).length,
        total: subtasks.length,
    };
}

export function getActiveTimer(task, authUserId) {
    return (task.time_logs || []).find(l => !l.stopped_at && l.user_id === authUserId);
}

export function totalLoggedMinutes(task) {
    return (task.time_logs || []).reduce((sum, l) => sum + (Number(l.minutes) || 0), 0);
}

const PREVIEWABLE_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'];

export function isPreviewableImage(att) {
    if (!att) return false;
    if (typeof att.mime_type === 'string' && att.mime_type.toLowerCase().startsWith('image/')) {
        return true;
    }
    const ext = String(att.file_name || '').split('.').pop()?.toLowerCase() || '';
    return PREVIEWABLE_IMAGE_EXTENSIONS.includes(ext);
}
