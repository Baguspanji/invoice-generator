<script setup>
import { computed } from 'vue';
import { statusDotClass } from './utils';

const props = defineProps({
    node: {
        type: Object,
        required: true,
    },
    activeId: {
        type: Number,
        default: null,
    },
    depth: {
        type: Number,
        default: 0,
    },
    baseUrl: {
        type: String,
        required: true,
    },
});

defineEmits(['create-child', 'delete']);

const isActive = computed(() => Number(props.activeId) === Number(props.node.id));
const href = computed(() => `${props.baseUrl}?page=${props.node.id}`);
</script>

<template>
    <li>
        <div class="group flex items-center gap-1 rounded-md py-1.5 pr-1 text-sm"
            :class="isActive
                ? 'bg-primary/10 font-semibold text-primary'
                : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'"
            :style="{ paddingLeft: `${depth * 12 + 8}px` }">
            <a :href="href" class="min-w-0 flex-1 truncate" :title="node.title">{{ node.title }}</a>

            <span class="h-1.5 w-1.5 shrink-0 rounded-full" :class="statusDotClass(node.status?.value)"></span>

            <button type="button" title="Tambah subhalaman"
                class="hidden shrink-0 rounded px-1 text-slate-400 hover:text-primary group-hover:block"
                @click="$emit('create-child', node.id)">+</button>
            <button type="button" title="Hapus halaman"
                class="hidden shrink-0 rounded px-1 text-slate-400 hover:text-red-500 group-hover:block"
                @click="$emit('delete', node.id)">&times;</button>
        </div>

        <ul v-if="node.children && node.children.length" class="space-y-1">
            <DocumentTreeNode v-for="child in node.children" :key="child.id" :node="child" :active-id="activeId"
                :depth="depth + 1" :base-url="baseUrl" @create-child="$emit('create-child', $event)"
                @delete="$emit('delete', $event)" />
        </ul>
    </li>
</template>
