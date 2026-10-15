<script setup>
import { ref } from 'vue';

const props = defineProps({
    name: {
        type: String,
        default: 'attachments[]',
    },
    label: {
        type: String,
        default: 'Lampiran Berkas (Multiple)',
    },
});

const isDragging = ref(false);
const files = ref([]);
const fileInput = ref(null);

function formatBytes(bytes) {
    if (!bytes || bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

function syncInput() {
    const dt = new DataTransfer();
    files.value.forEach(f => dt.items.add(f));
    if (fileInput.value) {
        fileInput.value.files = dt.files;
    }
}

function onDrop(e) {
    isDragging.value = false;
    const dropped = Array.from(e.dataTransfer?.files || []);
    if (!dropped.length) return;
    files.value.push(...dropped);
    syncInput();
}

function onSelect(e) {
    const selected = Array.from(e.target?.files || []);
    if (!selected.length) return;
    files.value.push(...selected);
    syncInput();
}

function removeFile(index) {
    files.value.splice(index, 1);
    syncInput();
}

function reset() {
    files.value = [];
    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

defineExpose({
    reset,
});
</script>

<template>
    <div class="space-y-2">
        <label v-if="label" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
            {{ label }}
        </label>

        <!-- Hidden Native File Input -->
        <input
            ref="fileInput"
            type="file"
            :name="name"
            multiple
            class="hidden"
            @change="onSelect"
        />

        <!-- Dropzone Card -->
        <div
            @click="fileInput?.click()"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="onDrop"
            class="group relative flex flex-col items-center justify-center rounded-xl border-2 border-dashed p-4 text-center transition cursor-pointer"
            :class="isDragging
                ? 'border-primary bg-blue-50/70 dark:border-primary dark:bg-blue-500/10'
                : 'border-slate-300 bg-slate-50/60 hover:border-primary hover:bg-blue-50/30 dark:border-slate-700 dark:bg-slate-800/40 dark:hover:border-slate-600 dark:hover:bg-slate-800/80'">
            
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white shadow-xs group-hover:scale-105 transition dark:bg-slate-700 text-slate-500 dark:text-slate-300">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
            </div>

            <p class="mt-2 text-xs font-semibold text-slate-700 dark:text-slate-200">
                <span class="text-primary hover:underline">Klik untuk pilih</span> atau seret & lepas berkas ke sini
            </p>
            <p class="text-[11px] text-slate-400">Mendukung banyak berkas (Multiple)</p>
        </div>

        <!-- Staged Files List -->
        <div v-if="files.length > 0" class="flex flex-col gap-1.5 max-h-36 overflow-y-auto pt-1">
            <div
                v-for="(f, idx) in files"
                :key="idx + f.name"
                class="flex items-center justify-between py-1.5 px-2.5 rounded-lg bg-slate-100/80 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs">
                <div class="flex items-center gap-2 truncate mr-2">
                    <svg class="h-4 w-4 shrink-0 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span class="font-medium text-slate-800 dark:text-slate-200 truncate">{{ f.name }}</span>
                    <span class="text-slate-400 shrink-0">({{ formatBytes(f.size) }})</span>
                </div>
                <button
                    type="button"
                    @click.stop="removeFile(idx)"
                    class="text-slate-400 hover:text-red-500 shrink-0 p-0.5 rounded transition"
                    title="Hapus berkas">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>
