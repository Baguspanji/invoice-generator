<script setup>
import { ref, reactive, computed, onMounted, nextTick, watch } from 'vue';
import Sortable from 'sortablejs';
import ProjectHeader from './ProjectHeader.vue';
import TaskFilterBar from './TaskFilterBar.vue';
import TaskListView from './TaskListView.vue';
import TaskKanbanView from './TaskKanbanView.vue';
import TaskCreateModal from './TaskCreateModal.vue';
import TaskEditModal from './TaskEditModal.vue';
import InvoiceGenerateModal from './InvoiceGenerateModal.vue';
import { isTaskCompleted } from './utils';

const props = defineProps({
    config: {
        type: Object,
        required: true,
    },
    title: {
        type: String,
        default: '',
    },
});

const cfg = props.config;
const project = cfg.project || {};
const viewMode = ref(cfg.viewMode || 'board');
const taskGroups = ref(JSON.parse(JSON.stringify(cfg.taskGroups || [])));
const users = cfg.users || [];
const labels = cfg.labels || [];
const unbilledTasks = ref(cfg.unbilledTasks || []);

// Modals
const showCreateModal = ref(false);
const createParentTask = ref(null);
const showEditModal = ref(false);
const showInvoiceModal = ref(false);
const selectedTask = ref(null);

// Completed tasks hidden by default, toggleable via UI
const showCompleted = ref(false);

const completedCount = computed(() => {
    return taskGroups.value.reduce(
        (sum, g) => sum + (g.tasks || []).reduce(
            (groupSum, t) => groupSum + [t, ...(t.subtasks || [])].filter(isTaskCompleted).length,
            0
        ),
        0
    );
});

// Accordion state for parent tasks, remembered per project
const expandedStorageKey = `project-${project.id}-expanded-tasks`;
const expandedTaskIds = ref(loadExpandedTaskIds());

function loadExpandedTaskIds() {
    try {
        return JSON.parse(localStorage.getItem(expandedStorageKey) || '[]');
    } catch {
        return [];
    }
}

watch(expandedTaskIds, (ids) => {
    localStorage.setItem(expandedStorageKey, JSON.stringify(ids));
}, { deep: true });

function expandableTaskIds() {
    return taskGroups.value.flatMap(g => (g.tasks || []).filter(t => (t.subtasks || []).length > 0).map(t => t.id));
}

const expandableCount = computed(() => expandableTaskIds().length);

const allExpanded = computed(() => {
    const ids = expandableTaskIds();
    return ids.length > 0 && ids.every(id => expandedTaskIds.value.includes(id));
});

function toggleExpandAll() {
    expandedTaskIds.value = allExpanded.value ? [] : expandableTaskIds();
}

function isExpanded(task) {
    return expandedTaskIds.value.includes(task.id);
}

function toggleExpand(task) {
    expandedTaskIds.value = isExpanded(task)
        ? expandedTaskIds.value.filter(id => id !== task.id)
        : [...expandedTaskIds.value, task.id];
}// Live filters for List View
const filters = reactive({
    search: cfg.filters?.search || '',
    priority: cfg.filters?.priority || '',
    user_id: cfg.filters?.user_id || '',
    label_id: cfg.filters?.label_id || '',
    billing: cfg.filters?.billing || '',
});

const hasActiveFilters = computed(() => {
    return Boolean(
        filters.search ||
        filters.priority ||
        filters.user_id ||
        filters.label_id ||
        filters.billing
    );
});

function resetFilters() {
    filters.search = '';
    filters.priority = '';
    filters.user_id = '';
    filters.label_id = '';
    filters.billing = '';
}

function matchesFilter(task) {
    if (filters.search) {
        const q = filters.search.toLowerCase();
        const text = `${task.title || ''} ${task.description || ''}`.toLowerCase();
        if (!text.includes(q)) return false;
    }
    if (filters.priority) {
        const pVal = typeof task.priority === 'object' && task.priority !== null ? task.priority.value : task.priority;
        if (pVal !== filters.priority) return false;
    }
    if (filters.user_id) {
        if (filters.user_id === 'unassigned') {
            if (task.assigned_to_user_id) return false;
        } else if (String(task.assigned_to_user_id) !== String(filters.user_id)) {
            return false;
        }
    }
    if (filters.label_id) {
        const taskLabels = (task.labels || []).map(l => String(l.id));
        if (!taskLabels.includes(String(filters.label_id))) return false;
    }
    if (filters.billing) {
        if (filters.billing === 'billable' && (!task.billable || task.invoice_id)) return false;
        if (filters.billing === 'invoiced' && !task.invoice_id) return false;
        if (filters.billing === 'non_billable' && task.billable) return false;
    }
    return true;
}

function visibleSubtasks(task) {
    return (task.subtasks || []).filter(s => (showCompleted.value || !isTaskCompleted(s)) && (!hasActiveFilters.value || matchesFilter(s)));
}

function filteredTasks(group) {
    return (group.tasks || []).filter(t => {
        const ownMatch = (showCompleted.value || !isTaskCompleted(t)) && matchesFilter(t);
        return ownMatch || visibleSubtasks(t).length > 0;
    });
}

function visibleBoardTasks(group) {
    return (group.tasks || []).filter(t => showCompleted.value || !isTaskCompleted(t));
}

function openEditModal(task) {
    selectedTask.value = task;
    showEditModal.value = true;
}

function openCreateModal(parentTask = null) {
    createParentTask.value = parentTask;
    showCreateModal.value = true;
}

function closeCreateModal() {
    showCreateModal.value = false;
    createParentTask.value = null;
}

// Drag & Drop Sortable Initialization
const sortableInstances = [];

function setupSortables() {
    sortableInstances.forEach(inst => inst.destroy());
    sortableInstances.length = 0;

    nextTick(() => {
        if (viewMode.value === 'board') {
            document.querySelectorAll('.kanban-tasks-container').forEach(el => {
                const inst = Sortable.create(el, {
                    group: 'kanban-tasks',
                    animation: 180,
                    ghostClass: 'opacity-40',
                    dragClass: 'shadow-lg',
                    handle: '.drag-handle',
                    onEnd: handleDragEnd,
                });
                sortableInstances.push(inst);
            });
        } else {
            document.querySelectorAll('.list-tasks-container').forEach(el => {
                const inst = Sortable.create(el, {
                    group: 'list-tasks',
                    animation: 180,
                    ghostClass: 'opacity-40',
                    handle: '.drag-handle',
                    draggable: 'tbody.list-task-item',
                    disabled: hasActiveFilters.value,
                    onEnd: handleDragEnd,
                });
                sortableInstances.push(inst);
            });
        }
    });
}

async function handleDragEnd(evt) {
    const taskId = Number(evt.item.dataset.taskId);
    const fromGroupId = Number(evt.from.dataset.groupId);
    const toGroupId = Number(evt.to.dataset.groupId);
    const newIndex = evt.newIndex;

    // Revert manual DOM movement so Vue handles virtual DOM rendering
    const siblings = evt.from.matches('table') ? evt.from.querySelectorAll(':scope > tbody.list-task-item') : evt.from.children;
    evt.from.insertBefore(evt.item, siblings[evt.oldIndex] || null);

    const fromGroup = taskGroups.value.find(g => g.id === fromGroupId);
    const toGroup = taskGroups.value.find(g => g.id === toGroupId);
    if (!fromGroup || !toGroup) return;

    const taskIndex = fromGroup.tasks.findIndex(t => t.id === taskId);
    if (taskIndex === -1) return;

    const [movedTask] = fromGroup.tasks.splice(taskIndex, 1);
    movedTask.task_group_id = toGroupId;
    (movedTask.subtasks || []).forEach(subtask => {
        subtask.task_group_id = toGroupId;
    });

    if (toGroup.name.toLowerCase() === 'done') {
        movedTask.completed_at = new Date().toISOString();
    } else if (fromGroup.name.toLowerCase() === 'done') {
        movedTask.completed_at = null;
    }

    toGroup.tasks.splice(newIndex, 0, movedTask);

    try {
        const response = await fetch(cfg.urls.moveTask, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': cfg.csrf,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                task_id: taskId,
                task_group_id: toGroupId,
                order_column: newIndex + 1,
                task_ids: toGroup.tasks.map(t => t.id),
            }),
        });

        if (!response.ok) {
            throw new Error('Gagal memindahkan task');
        }
    } catch (err) {
        console.error(err);
        alert('Gagal memindahkan task ke server.');
    }
}

watch([viewMode, hasActiveFilters, showCompleted], () => {
    // Sync URL mode without reload
    const url = new URL(window.location.href);
    url.searchParams.set('view', viewMode.value);
    window.history.replaceState({}, '', url.toString());
    setupSortables();
});

onMounted(() => {
    setupSortables();
});
</script>

<template>
    <div>
        <!-- Top Action Bar -->
        <ProjectHeader
            :project="project"
            :view-mode="viewMode"
            :unbilled-tasks-count="unbilledTasks.length"
            :urls="cfg.urls"
            @update:view-mode="viewMode = $event"
            @open-create-modal="openCreateModal()"
            @open-invoice-modal="showInvoiceModal = true"
        />

        <!-- LIST VIEW -->
        <div v-if="viewMode === 'list'">
            <TaskFilterBar
                :filters="filters"
                :has-active-filters="hasActiveFilters"
                :users="users"
                :labels="labels"
                :completed-count="completedCount"
                :expandable-count="expandableCount"
                :all-expanded="allExpanded"
                v-model:show-completed="showCompleted"
                @reset="resetFilters"
                @toggle-expand-all="toggleExpandAll"
            />

            <TaskListView
                :task-groups="taskGroups"
                :filtered-tasks="filteredTasks"
                :visible-subtasks="visibleSubtasks"
                :is-expanded="isExpanded"
                :cfg="cfg"
                :has-active-filters="hasActiveFilters"
                @edit-task="openEditModal"
                @toggle-expand="toggleExpand"
                @add-subtask="openCreateModal"
            />
        </div>

        <!-- KANBAN BOARD VIEW -->
        <div v-else>
            <div v-if="completedCount > 0" class="mb-4 flex justify-end">
                <button
                    type="button"
                    @click="showCompleted = !showCompleted"
                    class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-medium transition"
                    :class="showCompleted
                        ? 'border-slate-300 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300'
                        : 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:border-emerald-900/50 dark:bg-emerald-900/20 dark:text-emerald-400'">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    {{ showCompleted ? `Sembunyikan selesai (${completedCount})` : `Tampilkan selesai (${completedCount})` }}
                </button>
            </div>

            <TaskKanbanView
                :task-groups="taskGroups"
                :visible-tasks="visibleBoardTasks"
                :visible-subtasks="visibleSubtasks"
                :is-expanded="isExpanded"
                :cfg="cfg"
                @edit-task="openEditModal"
                @toggle-expand="toggleExpand"
            />
        </div>

        <!-- MODALS -->
        <TaskCreateModal
            :show="showCreateModal"
            :task-groups="taskGroups"
            :users="users"
            :labels="labels"
            :project="project"
            :cfg="cfg"
            :parent-task="createParentTask"
            @close="closeCreateModal"
        />

        <TaskEditModal
            :show="showEditModal"
            :task="selectedTask"
            :task-groups="taskGroups"
            :users="users"
            :labels="labels"
            :project="project"
            :cfg="cfg"
            @close="showEditModal = false"
        />

        <InvoiceGenerateModal
            :show="showInvoiceModal"
            :unbilled-tasks="unbilledTasks"
            :cfg="cfg"
            @close="showInvoiceModal = false"
        />
    </div>
</template>
