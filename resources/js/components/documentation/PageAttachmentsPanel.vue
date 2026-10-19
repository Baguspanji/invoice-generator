<script setup>
import { ref } from 'vue';

defineProps({
    attachments: {
        type: Array,
        default: () => [],
    },
    busy: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['upload', 'remove']);

const fileInput = ref(null);

function onChange(event) {
    const file = event.target.files?.[0];

    if (file) {
        emit('upload', file);
    }

    if (fileInput.value) {
        fileInput.value.value = '';
    }
}
</script>

<template>
    <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
        <h3 class="text-sm font-semibold text-dark dark:text-white">Lampiran</h3>

        <ul v-if="attachments.length" class="mt-3 space-y-2">
            <li v-for="attachment in attachments" :key="attachment.id"
                class="flex items-center justify-between gap-2 rounded-lg border border-slate-100 px-2.5 py-2 text-sm dark:border-slate-800">
                <a :href="attachment.url" target="_blank" rel="noopener"
                    class="min-w-0 flex-1 truncate text-primary hover:underline" :title="attachment.file_name">
                    {{ attachment.file_name }}
                </a>
                <span class="shrink-0 text-xs text-slate-400 dark:text-slate-500">{{ attachment.human_size }}</span>
                <button type="button" :disabled="busy" class="shrink-0 text-slate-400 hover:text-red-500 disabled:opacity-50"
                    @click="$emit('remove', attachment.id)">
                    &times;
                </button>
            </li>
        </ul>

        <p v-else class="mt-3 text-sm text-slate-400 dark:text-slate-500">Belum ada lampiran.</p>

        <div class="mt-3">
            <input ref="fileInput" type="file" :disabled="busy" class="block w-full text-sm text-slate-500"
                @change="onChange" />
        </div>
    </div>
</template>
