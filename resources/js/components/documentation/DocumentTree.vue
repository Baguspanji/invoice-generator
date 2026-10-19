<script setup>
import DocumentTreeNode from './DocumentTreeNode.vue';

defineProps({
    nodes: {
        type: Array,
        default: () => [],
    },
    activeId: {
        type: Number,
        default: null,
    },
    baseUrl: {
        type: String,
        required: true,
    },
});

defineEmits(['create-child', 'delete']);
</script>

<template>
    <ul v-if="nodes.length" class="space-y-1">
        <DocumentTreeNode v-for="node in nodes" :key="node.id" :node="node" :active-id="activeId" :depth="0"
            :base-url="baseUrl" @create-child="$emit('create-child', $event)" @delete="$emit('delete', $event)" />
    </ul>

    <p v-else class="px-2 py-3 text-sm text-slate-400 dark:text-slate-500">
        Belum ada halaman.
    </p>
</template>
