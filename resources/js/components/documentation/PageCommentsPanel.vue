<script setup>
import { ref } from 'vue';

defineProps({
    comments: {
        type: Array,
        default: () => [],
    },
    busy: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['add', 'remove']);

const draft = ref('');

function submit() {
    const body = draft.value.trim();

    if (body === '') {
        return;
    }

    emit('add', body);
    draft.value = '';
}
</script>

<template>
    <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
        <h3 class="text-sm font-semibold text-dark dark:text-white">Komentar</h3>

        <p v-if="!comments.length" class="mt-3 text-sm text-slate-400 dark:text-slate-500">
            Belum ada komentar.
        </p>

        <ul v-else class="mt-3 max-h-72 space-y-3 overflow-y-auto">
            <li v-for="comment in comments" :key="comment.id"
                class="rounded-lg bg-slate-50 p-2.5 dark:bg-slate-800">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-semibold text-slate-600 dark:text-slate-300">
                        {{ comment.user_name || 'Sistem' }}
                    </span>
                    <button v-if="comment.can_delete" type="button" :disabled="busy"
                        class="text-xs font-semibold text-red-500 hover:text-red-600 disabled:opacity-50"
                        @click="$emit('remove', comment.id)">
                        Hapus
                    </button>
                </div>
                <p class="mt-1 whitespace-pre-line text-sm text-slate-700 dark:text-slate-200">{{ comment.body }}</p>
                <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">{{ comment.created_at_label }}</p>
            </li>
        </ul>

        <div class="mt-3">
            <textarea v-model="draft" rows="3" placeholder="Tulis komentar…"
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"></textarea>
            <div class="mt-2 flex justify-end">
                <button type="button" :disabled="busy || draft.trim() === ''"
                    class="rounded-lg bg-primary px-3 py-1.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                    @click="submit">
                    Kirim
                </button>
            </div>
        </div>
    </div>
</template>
