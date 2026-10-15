<script setup>
import { watch, onUnmounted } from 'vue';

const props = defineProps({
    attachment: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close']);

function onKey(e) {
    if (e.key === 'Escape') emit('close');
}

watch(() => props.attachment, (att) => {
    if (att) {
        document.addEventListener('keydown', onKey);
        document.body.style.overflow = 'hidden';
    } else {
        document.removeEventListener('keydown', onKey);
        document.body.style.overflow = '';
    }
}, { immediate: true });

onUnmounted(() => {
    document.removeEventListener('keydown', onKey);
    document.body.style.overflow = '';
});
</script>

<template>
    <div
        v-if="attachment"
        class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/70 p-4"
        @click.self="$emit('close')">
        <div class="w-full max-w-3xl overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900">
            <!-- Header -->
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ attachment.file_name }}</p>
                    <p v-if="attachment.human_size" class="text-xs text-slate-400">{{ attachment.human_size }}</p>
                </div>
                <button
                    type="button"
                    @click="$emit('close')"
                    class="shrink-0 rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800"
                    title="Tutup pratinjau">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Image Body -->
            <div class="flex items-center justify-center bg-slate-950/90 p-2">
                <img
                    :src="attachment.url"
                    :alt="attachment.file_name"
                    class="max-h-[70vh] w-auto max-w-full rounded object-contain"
                />
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-end gap-2 border-t border-slate-100 px-4 py-3 dark:border-slate-800">
                <a
                    :href="attachment.url"
                    target="_blank"
                    rel="noopener"
                    class="rounded-lg border border-slate-200 bg-white px-3.5 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                    Buka Tab Baru
                </a>
                <a
                    :href="attachment.url"
                    download
                    class="rounded-lg bg-primary px-3.5 py-1.5 text-xs font-semibold text-white hover:bg-blue-700">
                    Unduh
                </a>
            </div>
        </div>
    </div>
</template>
